<?php

namespace App\Modules\Admissions\Actions;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Modules\Admissions\Models\AdmissionApplication;
use App\Modules\Admissions\Repositories\Contracts\AdmissionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Convert an accepted admission application into a real student account,
 * creating/linking a guardian (parent) and binding the school / class / section.
 * Mirrors the StudentController::store creation pattern (legacy User model at
 * project root + role_user pivot + parent_student pivot).
 */
final class ConvertToStudentAction
{
    public function __construct(private AdmissionRepository $applications) {}

    /**
     * @param  array{class_room_id?:int|null, section_id?:int|null}  $options
     * @return User the newly created student
     */
    public function execute(AdmissionApplication $application, array $options = []): User
    {
        return DB::transaction(function () use ($application, $options) {
            $student = $this->createStudent($application, $options);

            $this->linkGuardian($application, $student);

            $this->applications->update($application, [
                'status'               => 'completed',
                'converted_student_id' => $student->id,
                'reviewed_by'          => auth()->id(),
            ]);

            ActivityLog::log(
                'admissions.convert_to_student',
                "تحويل طلب القبول {$application->code} إلى طالب: {$student->name}",
                $application
            );

            return $student;
        });
    }

    private function createStudent(AdmissionApplication $application, array $options): User
    {
        $name  = $application->student_name ?: 'طالب '.$application->code;
        $plain = $application->national_id ?: Str::random(8);

        $username = $this->uniqueUsername($application->national_id ?: Str::slug($name, ''));
        $email    = $application->email ?: ($username.'@viewclass.local');

        $student = User::create(array_filter([
            'school_id'               => $application->school_id,
            'section_id'              => $options['section_id'] ?? null,
            'class_room_id'           => $options['class_room_id'] ?? null,
            'name'                    => $name,
            'name_ar'                 => $name,
            'username'                => $username,
            'email'                   => $email,
            'national_id'             => $application->national_id,
            'date_of_birth'           => $application->birth_date,
            'phone'                   => $application->phone,
            'nationality'             => $application->nationality,
            'address'                 => $application->address,
            'password'                => Hash::make($plain),
            'plain_password_for_card' => encrypt($plain),
            'is_active'               => true,
            'status'                  => 'active',
        ], static fn ($v) => $v !== null));

        if ($role = Role::where('slug', 'student')->first()) {
            $student->roles()->syncWithoutDetaching($role);
        }

        if (! empty($options['class_room_id'])) {
            DB::table('class_student')->insertOrIgnore([
                'class_id'   => $options['class_room_id'],
                'student_id' => $student->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $student;
    }

    /** Create a guardian if needed (matched by phone) and link to the student. */
    private function linkGuardian(AdmissionApplication $application, User $student): void
    {
        if (! $application->guardian_name && ! $application->phone) {
            return;
        }

        $parent = null;
        if ($application->phone) {
            $parent = User::where('school_id', $application->school_id)
                ->where('phone', $application->phone)
                ->whereHas('roles', fn ($r) => $r->where('slug', 'parent'))
                ->first();
        }

        if (! $parent) {
            $gName    = $application->guardian_name ?: ('ولي أمر '.$student->name);
            $username = $this->uniqueUsername(($application->phone ?: Str::slug($gName, '')).'p');
            $plain    = $application->phone ?: Str::random(8);

            $parent = User::create(array_filter([
                'school_id'               => $application->school_id,
                'name'                    => $gName,
                'name_ar'                 => $gName,
                'username'                => $username,
                'email'                   => $username.'@viewclass.local',
                'phone'                   => $application->phone,
                'password'                => Hash::make($plain),
                'plain_password_for_card' => encrypt($plain),
                'is_active'               => true,
                'status'                  => 'active',
            ], static fn ($v) => $v !== null));

            if ($role = Role::where('slug', 'parent')->first()) {
                $parent->roles()->syncWithoutDetaching($role);
            }
        }

        DB::table('parent_student')->insertOrIgnore([
            'parent_id'                  => $parent->id,
            'student_id'                 => $student->id,
            'relationship'              => 'ولي أمر',
            'is_primary'                => 1,
            'can_receive_notifications' => 1,
            'created_at'                => now(),
            'updated_at'                => now(),
        ]);
    }

    private function uniqueUsername(string $base): string
    {
        $base = preg_replace('/[^A-Za-z0-9_]/', '', $base) ?: 'std'.Str::random(5);
        $username = $base;
        $i = 1;
        while (User::where('username', $username)->exists()) {
            $username = $base.$i;
            $i++;
        }

        return $username;
    }
}
