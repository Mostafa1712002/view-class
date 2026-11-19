<?php

namespace App\Modules\RawaniHub\Actions;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Modules\RawaniHub\DTOs\BeneficiaryData;
use App\Modules\RawaniHub\Models\HubProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Finds a student by national ID or creates one (default school, optional parent link).
 * Collision rule (design §3.6): a national ID that already belongs to another user blocks self-service
 * ($selfService = true throws NationalIdCollision); an admin gets the existing student back and resolves it.
 */
final class FindOrCreateBeneficiary
{
    public function execute(BeneficiaryData $d, bool $selfService = false): User
    {
        $this->validate($d);

        return DB::transaction(function () use ($d, $selfService) {
            $existing = User::where('national_id', $d->nationalId)->first();
            if ($existing) {
                if ($selfService || ! $existing->hasRole('student')) {
                    throw new NationalIdCollision('يرجى التواصل مع الدعم');
                }

                return $existing;
            }

            $realEmail = $d->email && ! User::where('email', $d->email)->exists() ? $d->email : null;
            if ($d->email && ! $realEmail && $selfService) {
                throw new NationalIdCollision('يرجى التواصل مع الدعم');
            }
            $username = $this->username('rw');
            $student = User::create(array_filter([
                'school_id' => $this->schoolId(), 'name' => $d->name, 'name_ar' => $d->name,
                'username' => $username, 'email' => $realEmail ?? $username.'@viewclass.local',
                'national_id' => $d->nationalId, 'gender' => $d->gender, 'phone' => $d->phone,
                'password' => Hash::make(Str::random(20)), 'is_active' => true, 'status' => 'active',
            ], static fn ($v) => $v !== null));
            $this->assignRole($student, 'student');
            HubProfile::updateOrCreate(['user_id' => $student->id], ['stage' => $d->stage, 'grade' => $d->grade]);

            if ($d->parentNationalId) {
                $this->linkParent($student, $d);
            }
            ActivityLog::log('hub.beneficiary.create', "إنشاء طالب رواني: {$student->name}", $student, null, ['id' => $student->id, 'national_id' => $student->national_id]);

            return $student;
        });
    }

    private function linkParent(User $student, BeneficiaryData $d): void
    {
        $parent = User::where('national_id', $d->parentNationalId)->first();
        if ($parent && ! $parent->hasRole('parent')) {
            throw new NationalIdCollision('يرجى التواصل مع الدعم');
        }
        if (! $parent) {
            if (! $d->parentName) {
                throw ValidationException::withMessages(['parent_name' => 'required with a new parent']);
            }
            $username = $this->username('rwp');
            $parent = User::create([
                'school_id' => $this->schoolId(), 'name' => $d->parentName, 'name_ar' => $d->parentName,
                'username' => $username, 'email' => $username.'@viewclass.local',
                'national_id' => $d->parentNationalId, 'password' => Hash::make(Str::random(20)),
                'is_active' => true, 'status' => 'active',
            ]);
            $this->assignRole($parent, 'parent');
        }
        DB::table('parent_student')->insertOrIgnore([
            'parent_id' => $parent->id, 'student_id' => $student->id, 'relationship' => 'parent',
            'is_primary' => 1, 'can_receive_notifications' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function validate(BeneficiaryData $d): void
    {
        $e = [];
        trim($d->name) !== '' || $e['name'] = 'required';
        in_array($d->gender, ['male', 'female'], true) || $e['gender'] = 'male|female';
        in_array($d->stage, ['primary', 'intermediate', 'secondary'], true) || $e['stage'] = 'invalid';
        ($d->grade >= 1 && $d->grade <= 12) || $e['grade'] = '1-12';
        preg_match('/^\d{10}$/', $d->nationalId) || $e['national_id'] = '10 digits';
        ! $d->parentNationalId || preg_match('/^\d{10}$/', $d->parentNationalId) || $e['parent_national_id'] = '10 digits';
        if ($e) {
            throw ValidationException::withMessages($e);
        }
    }

    private function schoolId(): int
    {
        return (int) (config('rawani_hub.default_school_id') ?? throw new \RuntimeException('rawani_hub.default_school_id is not configured'));
    }

    private function username(string $prefix): string
    {
        do {
            $u = $prefix.Str::lower(Str::random(8));
        } while (User::where('username', $u)->exists());

        return $u;
    }

    private function assignRole(User $user, string $slug): void
    {
        if ($role = Role::where('slug', $slug)->first()) {
            $user->roles()->syncWithoutDetaching($role);
        }
    }
}
