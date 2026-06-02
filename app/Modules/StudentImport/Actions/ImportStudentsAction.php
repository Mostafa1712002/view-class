<?php

namespace App\Modules\StudentImport\Actions;

use App\Models\Role;
use App\Models\StudentProfile;
use App\Models\User;
use App\Modules\StudentImport\DTOs\StudentImportResult;
use App\Modules\StudentImport\DTOs\StudentImportRowDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Applies the classified Excel-import preview rows to the platform.
 *
 *  - Matches existing students by national_id (within the school) → UPDATE.
 *  - Otherwise CREATE.
 *  - Writes the student_profiles row, links the class pivot, and best-effort
 *    creates/links father (primary) + mother parent accounts.
 *
 * Hard safety rules (card #108 + global):
 *  - An EXISTING student's password is NEVER changed.
 *  - On CREATE the password is the supplied cell, else generated from the id.
 */
final class ImportStudentsAction
{
    /**
     * Execute from the classified preview arrays persisted at preview time.
     * Invalid + duplicate rows are skipped (counted as failed).
     *
     * @param  array<int, array<string, mixed>>  $previewRows
     */
    public function executeFromPreview(array $previewRows, int $schoolId): StudentImportResult
    {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $parentCreated = 0;
        $errors = [];

        $studentRole = Role::where('slug', 'student')->first();
        $parentRole = Role::where('slug', 'parent')->first();

        foreach ($previewRows as $pr) {
            $status = $pr['status'] ?? 'new';

            if (in_array($status, ['invalid', 'duplicate'], true)) {
                $failed++;
                $errors[] = ['row' => $pr['rowNumber'] ?? '', 'reason' => $pr['reason'] ?? __('student_import.errors.skipped')];

                continue;
            }

            $row = StudentImportRowDto::fromArray($pr);
            $sectionId = $pr['resolvedSectionId'] ?? null;
            $classId = $pr['resolvedClassId'] ?? null;

            try {
                DB::transaction(function () use ($row, $schoolId, $sectionId, $classId, $studentRole, $parentRole, &$created, &$updated, &$parentCreated) {
                    $existing = User::withTrashed()
                        ->where('national_id', $row->nationalId)
                        ->where('school_id', $schoolId)
                        ->first();

                    $base = $this->userBase($row, $schoolId, $sectionId, $classId);

                    if ($existing) {
                        // Never touch password on update.
                        $existing->fill($base)->save();
                        $student = $existing;
                        $updated++;
                    } else {
                        $plain = $row->password ?: (string) $row->nationalId;
                        $base['username'] = $row->username ?: $this->safeUsername((string) $row->nationalId);
                        $base['password'] = Hash::make($plain);
                        $base['plain_password_for_card'] = encrypt($plain);
                        $base['is_active'] = true;
                        $base['status'] = 'active';
                        $student = User::create($base);
                        $created++;
                    }

                    if ($studentRole) {
                        $student->roles()->syncWithoutDetaching($studentRole);
                    }

                    $this->syncProfile($student->id, $row);

                    if ($classId) {
                        DB::table('class_student')->insertOrIgnore([
                            'class_id' => $classId,
                            'student_id' => $student->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // Best-effort parents: failure here must not roll back the student.
                    $parentCreated += $this->linkParents($row, $schoolId, $student, $parentRole);
                });
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = ['row' => $row->rowNumber, 'reason' => $e->getMessage()];
            }
        }

        return new StudentImportResult(
            total: count($previewRows),
            created: $created,
            updated: $updated,
            failed: $failed,
            parentCreated: $parentCreated,
            errors: $errors,
            status: 'completed',
        );
    }

    /** Build the users-table payload shared by create + update (no password keys). */
    private function userBase(StudentImportRowDto $row, int $schoolId, ?int $sectionId, ?int $classId): array
    {
        $name = $row->fullName();
        $nameEn = trim(implode(' ', array_filter([
            $row->firstNameEn, $row->fatherNameEn, $row->grandfatherNameEn, $row->lastNameEn,
        ], fn ($v) => $v !== null && trim((string) $v) !== '')));

        $username = $row->username ?: $this->safeUsername((string) $row->nationalId);

        return array_filter([
            'school_id' => $schoolId,
            'section_id' => $sectionId,
            'class_room_id' => $classId,
            'name' => $name,
            'name_ar' => $name,
            'name_en' => $nameEn ?: null,
            'national_id' => $row->nationalId,
            'username' => $username,
            'email' => $row->email ?: ($username.'@viewclass.local'),
            'gender' => $this->normalizeGender($row->gender),
            'date_of_birth' => $this->parseDate($row->birthDate),
            'phone' => $row->mobile,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function syncProfile(int $userId, StudentImportRowDto $row): void
    {
        StudentProfile::updateOrCreate(
            ['user_id' => $userId],
            array_filter([
                'first_name' => $row->firstName,
                'father_name' => $row->fatherName,
                'grandfather_name' => $row->grandfatherName,
                'last_name' => $row->lastName,
                'first_name_en' => $row->firstNameEn,
                'father_name_en' => $row->fatherNameEn,
                'grandfather_name_en' => $row->grandfatherNameEn,
                'last_name_en' => $row->lastNameEn,
                'fingerprint_id' => $row->fingerprintId,
                'seat_number' => $row->seatNumber,
                'passport_number' => $row->passportId,
                'nationality' => $row->nationality,
                'academic_id' => $row->academicId,
                'birth_place' => $row->birthPlace,
                'admission_year' => $this->parseYear($row->acceptanceYear),
                'previous_school' => $row->previousSchool,
                'father_national_id' => $row->fatherNationalId,
                'mother_national_id' => $row->motherNationalId,
                'mother_full_name' => $row->motherFullName,
            ], fn ($v) => $v !== null && $v !== '')
        );
    }

    /** @return int number of parent accounts created */
    private function linkParents(StudentImportRowDto $row, int $schoolId, User $student, ?Role $parentRole): int
    {
        $created = 0;
        $created += $this->upsertParent($schoolId, $student, $parentRole, $row->fatherNationalId, $row->fatherName, $row->fatherMobile, true) ? 1 : 0;
        $created += $this->upsertParent($schoolId, $student, $parentRole, $row->motherNationalId, $row->motherFullName, $row->motherMobile, false) ? 1 : 0;

        return $created;
    }

    /** @return bool true when a NEW parent account was created */
    private function upsertParent(int $schoolId, User $student, ?Role $parentRole, ?string $nationalId, ?string $name, ?string $phone, bool $primary): bool
    {
        $pid = trim((string) $nationalId);
        if ($pid === '') {
            return false;
        }

        $parent = User::withTrashed()
            ->where('national_id', $pid)
            ->where('school_id', $schoolId)
            ->first();

        $wasCreated = false;
        $displayName = $name ?: ('ولي أمر '.$pid);

        if ($parent) {
            $parent->fill(array_filter([
                'name' => $name ?: $parent->name,
                'name_ar' => $name ?: $parent->name_ar,
                'phone' => $phone,
            ], fn ($v) => $v !== null && $v !== ''))->save();
        } else {
            $username = $this->safeUsername($pid);
            $parent = User::create(array_filter([
                'name' => $displayName,
                'name_ar' => $displayName,
                'school_id' => $schoolId,
                'national_id' => $pid,
                'username' => $username,
                'phone' => $phone,
                'email' => $username.'@viewclass.local',
                'gender' => $primary ? 'male' : 'female',
                'is_active' => true,
                'status' => 'active',
                'password' => Hash::make($pid),
                'plain_password_for_card' => encrypt($pid),
            ], fn ($v) => $v !== null && $v !== ''));
            $wasCreated = true;
        }

        if ($parentRole) {
            $parent->roles()->syncWithoutDetaching($parentRole);
        }

        if (! DB::table('parent_student')
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->exists()) {
            DB::table('parent_student')->insert([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'relationship' => $primary ? 'father' : 'mother',
                'is_primary' => $primary,
                'can_receive_notifications' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $wasCreated;
    }

    private function safeUsername(string $base): string
    {
        $base = preg_replace('/\D+/', '', $base) ?: Str::random(8);
        $candidate = $base;
        $i = 1;
        while (User::where('username', $candidate)->exists()) {
            $candidate = $base.'-'.$i++;
        }

        return $candidate;
    }

    private function normalizeGender(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }
        $raw = trim($raw);
        if (in_array($raw, ['ذكر', 'M', 'm', 'male', 'Male', 'MALE'], true)) {
            return 'male';
        }
        if (in_array($raw, ['أنثى', 'انثى', 'F', 'f', 'female', 'Female', 'FEMALE'], true)) {
            return 'female';
        }

        return null;
    }

    private function parseDate(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $raw = trim($raw);
        // Excel serial date (readDataOnly returns the raw serial for date cells).
        if (is_numeric($raw) && (float) $raw > 10000 && (float) $raw < 100000) {
            $ts = ((int) $raw - 25569) * 86400;

            return gmdate('Y-m-d', $ts);
        }
        $ts = strtotime($raw);

        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function parseYear(?string $raw): ?int
    {
        if ($raw === null) {
            return null;
        }
        if (preg_match('/(\d{4})/', $raw, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
