<?php

namespace Tests\Unit;

use App\Modules\RawaniHub\DTOs\PlatformResult;
use App\Modules\RawaniHub\Services\PlatformClient;
use Illuminate\Foundation\Testing\TestCase;
use Tests\Support\FakePlatform;

class PlatformClientTest extends TestCase
{
    private const SECRET = 'test-secret-0123456789abcdef0123456789abcdef';

    public function test_put_signature_vector(): void
    {
        $body = '{"enabled":true}';
        $this->assertSame('26b3426b2593763c96d0890b4a77a0bbf66d13fc512b0c6b138a23c290f30a2a', hash('sha256', $body));
        $this->assertSame(
            '9117d1e5c2e4be4d2455f1971c4b5b1fd16761260eb8e4b05d7486d4563b3672',
            PlatformClient::sign(self::SECRET, 'PUT', '/accounts/student/42', 1763550000, '6f1c2a7e-4b1d-4c55-9a3e-2d7f8b9c0a11', $body)
        );
    }

    public function test_get_query_signature_vector(): void
    {
        $path = '/people'.PlatformClient::queryString(['per' => 500, 'cursor' => 'a b/c', 'empty' => '']);
        $this->assertSame('/people?cursor=a%20b%2Fc&per=500', $path);
        $this->assertSame(
            '048cf64cf8727ac5b6688acff0a2b9a68d134b65ff99fb5c49f5350ee9985745',
            PlatformClient::sign(self::SECRET, 'GET', $path, 1763550000, '0b7e1c4a-9d2f-4e61-8a3b-5c6d7e8f9a01', '')
        );
    }

    public function test_fake_platform_flow_and_error_mapping(): void
    {
        $fake = FakePlatform::install();
        $c = $fake->client();

        $this->assertTrue($c->ping()->ok());
        $put = $c->putAccount('student', 42, ['enabled' => true, 'profile' => ['national_id' => '123']]);
        $this->assertTrue($put->ok());
        $this->assertTrue($put->data['created']);
        $this->assertFalse($c->putAccount('student', 42, ['enabled' => true])->data['created']);
        $this->assertTrue($c->sso('student', 42)->ok());
        $this->assertSame(PlatformResult::NOT_FOUND, $c->sso('student', 7)->status);

        $fake->conflictOn('student', '999', '55', 'old');
        $r = $c->putAccount('student', 43, ['profile' => ['national_id' => '999']]);
        $this->assertSame(PlatformResult::CONFLICT, $r->status);
        $this->assertSame('55', $r->details['external_id']);

        foreach ([[422, 'VALIDATION_FAILED', PlatformResult::INVALID], [429, 'PLATFORM_ERROR', PlatformResult::RETRYABLE],
            [503, 'PLATFORM_ERROR', PlatformResult::RETRYABLE], [501, 'NOT_SUPPORTED', PlatformResult::UNSUPPORTED],
            [401, 'REPLAYED_NONCE', PlatformResult::AUTH]] as [$s, $code, $want]) {
            $fake->failNext($s, $code);
            $r = $c->ping();
            $this->assertSame($want, $r->status);
            $this->assertSame($code, $r->code);
        }
    }

    public function test_bad_secret_is_auth_and_people_paginate(): void
    {
        $fake = FakePlatform::install();
        $this->assertSame(PlatformResult::AUTH, (new PlatformClient(FakePlatform::BASE, 'wrong'))->ping()->status);

        $fake->people = [['external_id' => '1'], ['external_id' => '2'], ['external_id' => '3']];
        $p1 = $fake->client()->people(null, 2);
        $this->assertCount(2, $p1->data['items']);
        $p2 = $fake->client()->people($p1->data['next_cursor'], 2);
        $this->assertCount(1, $p2->data['items']);
        $this->assertNull($p2->data['next_cursor']);
    }
}
