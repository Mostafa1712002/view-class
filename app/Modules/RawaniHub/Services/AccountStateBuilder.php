<?php

namespace App\Modules\RawaniHub\Services;

use App\Models\User;
use App\Modules\RawaniHub\DTOs\AccountState;
use App\Modules\RawaniHub\Models\HubPlatform;
use App\Modules\RawaniHub\Models\HubPlatformAccount;
use App\Modules\RawaniHub\Models\HubProfile;
use Carbon\CarbonInterface;

/** Builds the full state for one account when the sync job runs (design §3.2). */
class AccountStateBuilder
{
    /** Decision Q7: aafaq kids log in with a generated placeholder email. */
    public const AAFAQ_PLACEHOLDER_DOMAIN = 'rawaniedu.com';

    public function __construct(private EntitlementCalculator $calculator) {}

    /**
     * @param  User  $user  the person behind the account (the parent user for a parent row)
     * @param  iterable  $subscriptions  the beneficiary's subscriptions (only used for student rows)
     * @param  User|null  $parent  the beneficiary's primary parent, for platforms with capabilities.parents
     * @param  bool  $sendPassword  true only on create/reset: the stored password is then included
     */
    public function build(
        HubPlatformAccount $account,
        HubPlatform $platform,
        User $user,
        ?HubProfile $profile,
        iterable $subscriptions,
        CarbonInterface $now,
        ?User $parent = null,
        bool $sendPassword = false,
    ): AccountState {
        $role = $account->role;
        $isStudent = $role === 'student';

        $values = [
            'name' => $user->name,
            'gender' => $user->gender,
            'national_id' => $user->national_id,
            'email' => $user->email,
            'phone' => $user->phone,
        ];
        if ($isStudent) {
            $values['stage'] = $profile?->stage;
            $values['grade'] = $profile?->grade;
            if ($platform->slug === 'aafaq') {
                $values['email'] = 'rw'.$user->id.'@'.self::AAFAQ_PLACEHOLDER_DOMAIN;
            }
        }
        // The contract wants a real email or null; {username}@viewclass.local is ViewClass's own login placeholder.
        if (is_string($values['email']) && str_ends_with(strtolower($values['email']), '@viewclass.local')) {
            $values['email'] = null;
        }
        foreach ($values as $k => $v) {
            $values[$k] = ($v === '' || $v === null) ? null : $v;
        }
        if (isset($values['grade'])) {
            $values['grade'] = (int) $values['grade'];
        }
        if ($isStudent && ($platform->capabilities['parents'] ?? false) && $parent) {
            $values['parent_rawani_id'] = $parent->id;
        }

        $missing = [];
        foreach ($platform->required_fields[$role] ?? [] as $field) {
            if (! isset($values[$field])) {
                $missing[] = $field;
            }
        }

        // A parent row carries no entitlements (§3.2); only students get windows.
        $entitlements = $isStudent
            ? $this->calculator->entitlements($subscriptions, $platform->id, $now)
            : [];

        $link = (! $account->managed && $account->link_confirmed_by && $account->external_id)
            ? (string) $account->external_id : null;

        return new AccountState(
            profile: $values,
            enabled: (bool) $account->enabled,
            entitlements: $entitlements,
            password: $sendPassword ? ($account->password ?: null) : null,
            linkExternalId: $link,
            missing: $missing,
        );
    }
}
