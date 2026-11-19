<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Modules\RawaniHub\Models\HubProfile;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Services\LinkCandidates;
use App\Modules\RawaniHub\Support\RequiresSuperAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * «إنشاء شخص في رواني» from an unlinked directory row (task 4.3): the admin completes the form prefilled
 * from the mirror, a new Rawani person is created in the hub school, then linked through LinkPlatformAccount.
 * A national ID that already belongs to someone is refused: that person is linked instead, never duplicated.
 *
 * @param  array{name:string,gender:string,national_id?:?string,email?:?string,phone?:?string,stage?:?string,grade?:?int}  $data
 */
final class CreatePersonFromAccount
{
    use RequiresSuperAdmin;

    public function __construct(private PlatformAccountRepository $accounts, private LinkPlatformAccount $link) {}

    public function execute(User $actor, int $accountId, array $data): User
    {
        $this->authorizeHubAdmin($actor);

        return DB::transaction(function () use ($actor, $accountId, $data) {
            $account = $this->accounts->find($accountId) ?? throw ValidationException::withMessages(['account' => 'not found']);
            if ($account->user_id || ! in_array($account->role, ['student', 'parent'], true)) {
                throw ValidationException::withMessages(['account' => __('hub_directory.create_only_unlinked')]);
            }
            $nationalId = $data['national_id'] ?? null;
            if ($nationalId && User::where('national_id', $nationalId)->exists()) {
                throw ValidationException::withMessages(['national_id' => __('hub_directory.national_id_taken')]);
            }

            $email = LinkCandidates::realEmail($data['email'] ?? null);
            $email = $email && ! User::where('email', $email)->exists() ? $email : null;
            do {
                $username = ($account->role === 'parent' ? 'rwp' : 'rw').Str::lower(Str::random(8));
            } while (User::where('username', $username)->exists());

            $person = User::create(array_filter([
                'school_id' => (int) (config('rawani_hub.default_school_id') ?? throw new \RuntimeException('rawani_hub.default_school_id is not configured')),
                'name' => $data['name'], 'name_ar' => $data['name'], 'username' => $username,
                'email' => $email ?? $username.'@viewclass.local', 'national_id' => $nationalId,
                'gender' => $data['gender'], 'phone' => $data['phone'] ?? null,
                'password' => Hash::make(Str::random(20)), 'is_active' => true, 'status' => 'active',
            ], static fn ($v) => $v !== null));
            if ($role = Role::where('slug', $account->role)->first()) {
                $person->roles()->syncWithoutDetaching($role);
            }
            if ($account->role === 'student' && (($data['stage'] ?? null) || ($data['grade'] ?? null))) {
                HubProfile::updateOrCreate(['user_id' => $person->id], ['stage' => $data['stage'] ?? null, 'grade' => $data['grade'] ?? null]);
            }
            ActivityLog::log('hub.person.create_from_account', "إنشاء شخص رواني من حساب المنصة #{$account->id}: {$person->name}", $person, null,
                ['id' => $person->id, 'account_id' => $account->id, 'national_id' => $person->national_id]);

            $this->link->execute($actor, $account->id, userId: $person->id);

            return $person;
        });
    }
}
