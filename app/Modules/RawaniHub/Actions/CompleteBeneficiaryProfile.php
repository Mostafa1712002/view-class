<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubProfile;
use App\Modules\RawaniHub\Services\CheckoutReadiness;
use Illuminate\Support\Facades\DB;

/**
 * The buyer fills the blanks a package needs: national ID / gender / stage / grade of the beneficiary and, for a parent
 * buyer, their own national ID. Only empty fields are filled (identity data is never overwritten), and a national ID
 * already held by another user stops self-service (design §3.6).
 */
final class CompleteBeneficiaryProfile
{
    public function __construct(private CheckoutReadiness $readiness) {}

    /** @param array<string,mixed> $data keys: national_id, gender, stage, grade, parent_national_id */
    public function execute(User $actor, User $beneficiary, array $data): void
    {
        DB::transaction(function () use ($actor, $beneficiary, $data) {
            $this->fill($beneficiary, ['national_id' => $data['national_id'] ?? null, 'gender' => $data['gender'] ?? null]);

            $profile = HubProfile::firstOrNew(['user_id' => $beneficiary->id]);
            foreach (['stage', 'grade'] as $f) {
                if (! $profile->{$f} && ! empty($data[$f])) {
                    $profile->{$f} = $data[$f];
                }
            }
            $profile->save();

            if ($actor->id !== $beneficiary->id && $actor->id === $this->readiness->primaryParent($beneficiary)?->id) {
                $this->fill($actor, ['national_id' => $data['parent_national_id'] ?? null]);
            }
            ActivityLog::log('hub.beneficiary.complete', "استكمال بيانات: {$beneficiary->name}", $beneficiary, null, ['by' => $actor->id]);
        });
    }

    private function fill(User $user, array $values): void
    {
        foreach ($values as $field => $value) {
            if ($value === null || $value === '' || $user->{$field}) {
                continue;
            }
            if ($field === 'national_id' && User::where('national_id', $value)->where('id', '!=', $user->id)->exists()) {
                throw new NationalIdCollision('يرجى التواصل مع الدعم');
            }
            $user->{$field} = $value;
        }
        $user->save();
    }
}
