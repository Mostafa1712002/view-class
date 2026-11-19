<?php

namespace Tests\Support;

use App\Modules\RawaniHub\Services\PlatformClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * In-memory implementation of the rawani/v1 contract over Http::fake.
 * Verifies signature, timestamp and nonce replay like a real platform.
 */
class FakePlatform
{
    public const SECRET = 'fake-platform-secret';
    public const BASE = 'https://fake-platform.test/api/rawani/v1';

    /** @var array<string,array> "role:rawani_id" => account */
    public array $accounts = [];
    /** @var array<string,array{external_id:string,login:string}> "role:natural_key" => existing unlinked account */
    public array $conflicts = [];
    /** @var list<array{status:int,code:string,message?:string,details?:array}> consumed one per call, before normal handling */
    public array $failures = [];
    public array $people = [];
    public array $capabilities = ['sso' => true, 'parents' => true];
    public array $requests = [];
    /** Overrides the URL the sso endpoint answers with. */
    public ?string $ssoUrl = null;
    /** Runs inside an authenticated request, before it is answered (simulates a change landing mid-call). */
    public ?\Closure $during = null;
    private array $nonces = [];
    private int $nextId = 1000;

    public static function install(): self
    {
        $fake = new self();
        Http::fake([self::BASE.'/*' => fn (Request $r) => $fake->handle($r)]);

        return $fake;
    }

    public function client(): PlatformClient
    {
        return new PlatformClient(self::BASE, self::SECRET);
    }

    public function failNext(int $status, string $code, array $details = []): void
    {
        $this->failures[] = ['status' => $status, 'code' => $code, 'details' => $details];
    }

    public function conflictOn(string $role, string $naturalKey, string $externalId = '9001', string $login = 'existing'): void
    {
        $this->conflicts["$role:$naturalKey"] = ['external_id' => $externalId, 'login' => $login];
    }

    public function handle(Request $r)
    {
        $this->requests[] = $r;
        $url = parse_url($r->url());
        $logical = substr($url['path'], strlen(parse_url(self::BASE, PHP_URL_PATH)));
        $query = isset($url['query']) ? '?'.$url['query'] : '';
        $method = $r->method();

        if ($err = $this->authError($r, $method, $logical.$query)) {
            return $err;
        }
        if ($this->during) {
            ($this->during)();
        }
        if ($this->failures) {
            $f = array_shift($this->failures);

            return $this->error($f['status'], $f['code'], $f['message'] ?? $f['code'], $f['details'] ?? []);
        }

        if ($method === 'GET' && $logical === '/ping') {
            return $this->ok(['platform' => 'fake', 'contract' => 'rawani/v1', 'time' => gmdate('Y-m-d\TH:i:s\Z'), 'capabilities' => $this->capabilities]);
        }
        if ($method === 'GET' && $logical === '/people') {
            parse_str($url['query'] ?? '', $q);
            $per = (int) ($q['per'] ?? 500);
            $after = (int) ($q['cursor'] ?? 0);
            $rows = array_values(array_filter($this->people, fn ($p) => (int) $p['external_id'] > $after));
            $page = array_slice($rows, 0, $per);
            $next = count($rows) > $per ? (string) end($page)['external_id'] : null;

            return $this->ok(['items' => $page, 'next_cursor' => $next]);
        }
        if (preg_match('#^/accounts/(student|parent)/(\d+)(/sso)?$#', $logical, $m)) {
            [, $role, $id] = $m;
            $key = "$role:$id";
            if (! empty($m[3])) {
                if (empty($this->capabilities['sso'])) {
                    return $this->error(501, 'NOT_SUPPORTED', 'sso not supported');
                }
                if (! isset($this->accounts[$key])) {
                    return $this->error(404, 'NOT_FOUND', 'no account');
                }

                return $this->ok(['url' => $this->ssoUrl ?? 'https://fake-platform.test/rawani/sso/'.str_repeat('a', 64), 'expires_at' => gmdate('Y-m-d\TH:i:s\Z', time() + 60)]);
            }
            if ($method === 'PUT') {
                return $this->upsert($key, $role, (array) $r->data());
            }
        }

        return $this->error(404, 'NOT_FOUND', 'unknown route');
    }

    private function upsert(string $key, string $role, array $state)
    {
        $created = ! isset($this->accounts[$key]);
        if ($created && empty($state['link_external_id'])) {
            $natural = (string) ($state['profile']['national_id'] ?? $state['profile']['email'] ?? '');
            if ($natural !== '' && isset($this->conflicts["$role:$natural"])) {
                $c = $this->conflicts["$role:$natural"];

                return $this->error(409, 'ACCOUNT_EXISTS', 'account exists', $c);
            }
        }
        if ($created) {
            $this->accounts[$key] = [
                'external_id' => $state['link_external_id'] ?? (string) $this->nextId++,
                'login' => 'login'.$this->nextId,
                'managed' => empty($state['link_external_id']),
            ];
        }
        $acc = &$this->accounts[$key];
        $acc['state'] = $state;
        $acc['enabled'] = $state['enabled'] ?? true;
        $acc['entitlements'] = $state['entitlements'] ?? [];
        if (isset($state['password']) && $acc['managed']) {
            $acc['password'] = $state['password'];
        }

        return $this->ok(['external_id' => $acc['external_id'], 'login' => $acc['login'], 'created' => $created, 'managed' => $acc['managed']]);
    }

    private function authError(Request $r, string $method, string $pathWithQuery)
    {
        $ts = (int) $r->header('X-Rawani-Timestamp')[0] ?? 0;
        $nonce = $r->header('X-Rawani-Nonce')[0] ?? '';
        $sig = $r->header('X-Rawani-Signature')[0] ?? '';
        if (abs(time() - $ts) > 300) {
            return $this->error(401, 'STALE_TIMESTAMP', 'stale');
        }
        $expected = PlatformClient::sign(self::SECRET, $method, $pathWithQuery, $ts, $nonce, $r->body());
        if (! hash_equals($expected, $sig)) {
            return $this->error(401, 'INVALID_SIGNATURE', 'bad signature');
        }
        if (isset($this->nonces[$nonce])) {
            return $this->error(401, 'REPLAYED_NONCE', 'replayed');
        }
        $this->nonces[$nonce] = true;

        return null;
    }

    private function ok(array $data)
    {
        return Http::response(['success' => true, 'data' => $data, 'message' => ''], 200);
    }

    private function error(int $status, string $code, string $message, array $details = [])
    {
        return Http::response(['success' => false, 'error' => ['code' => $code, 'message' => $message, 'details' => (object) $details]], $status);
    }
}
