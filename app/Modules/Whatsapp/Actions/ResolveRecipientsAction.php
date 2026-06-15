<?php

namespace App\Modules\Whatsapp\Actions;

use App\Models\User;
use App\Modules\Whatsapp\Repositories\Contracts\RecipientRepository;
use App\Modules\Whatsapp\Support\PhoneNormalizer;
use Illuminate\Support\Collection;

/**
 * Resolves a chosen recipient group (or explicit user ids) into a deduplicated
 * list of recipients with display data and per-number validity status.
 */
final class ResolveRecipientsAction
{
    public function __construct(private readonly RecipientRepository $repo) {}

    /**
     * @param  array<int>  $explicitIds  used when audience = specific_users
     * @return array<int, array{id:int,name:string,role:string,number:?string,number_status:string,number_status_label:string}>
     */
    public function execute(string $audience, ?int $schoolId, ?int $refId = null, array $explicitIds = []): array
    {
        $users = $audience === 'specific_users'
            ? $this->repo->findUsers($explicitIds, $schoolId)
            : $this->repo->resolveAudience($audience, $schoolId, $refId);

        return $this->shape($users);
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<int, array<string, mixed>>
     */
    private function shape(Collection $users): array
    {
        $seenNumbers = [];
        $out         = [];

        foreach ($users->unique('id') as $user) {
            /** @var User $user */
            $raw        = $user->whatsapp ?? $user->phone ?? null;
            $normalized = PhoneNormalizer::normalize($raw);

            if ($normalized === null) {
                $status = 'no_number';
            } elseif (! PhoneNormalizer::isValid($normalized)) {
                $status = 'invalid_number';
            } elseif (isset($seenNumbers[$normalized])) {
                $status = 'duplicate';
            } else {
                $status               = 'valid';
                $seenNumbers[$normalized] = true;
            }

            $out[] = [
                'id'                  => $user->id,
                'name'                => $user->name,
                'role'                => $user->role_name,
                'number'              => $normalized,
                'number_status'       => $status,
                'number_status_label' => self::statusLabel($status),
            ];
        }

        return $out;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'valid'          => 'صحيح',
            'no_number'      => 'لا يوجد رقم',
            'invalid_number' => 'رقم غير صحيح',
            'duplicate'      => 'مكرر',
            default          => $status,
        };
    }
}
