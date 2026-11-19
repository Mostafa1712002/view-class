<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Public «سجّل واشترِ»: creates a parent account in the default school. */
final class RegisterBuyer
{
    /** @param array{name:string,gender:string,national_id:string,phone:string,email:string,password:string} $d
     * @throws NationalIdCollision on any email/national-ID clash (the caller must not say which) */
    public function execute(array $d): User
    {
        if (User::where('email', $d['email'])->orWhere('national_id', $d['national_id'])->exists()) {
            throw new NationalIdCollision('exists');
        }
        $schoolId = config('rawani_hub.default_school_id') ?? throw new \RuntimeException('rawani_hub.default_school_id is not configured');

        try {
            return DB::transaction(function () use ($d, $schoolId) {
                do {
                    $username = 'rwp'.Str::lower(Str::random(8));
                } while (User::where('username', $username)->exists());
                $user = User::create([
                    'school_id' => $schoolId, 'name' => $d['name'], 'name_ar' => $d['name'], 'username' => $username,
                    'email' => $d['email'], 'national_id' => $d['national_id'], 'gender' => $d['gender'], 'phone' => $d['phone'],
                    'password' => Hash::make($d['password']), 'is_active' => true, 'status' => 'active',
                ]);
                if ($role = Role::where('slug', 'parent')->first()) {
                    $user->roles()->syncWithoutDetaching($role);
                }
                ActivityLog::log('hub.register', "تسجيل ولي أمر جديد: {$user->name}", $user, null, ['id' => $user->id]);

                return $user;
            });
        } catch (QueryException) {
            throw new NationalIdCollision('exists'); // lost a race on a unique column
        }
    }
}
