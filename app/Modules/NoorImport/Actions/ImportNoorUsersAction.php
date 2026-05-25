<?php

namespace App\Modules\NoorImport\Actions;

use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\Section;
use App\Models\User;
use App\Modules\NoorImport\DTOs\NoorImportResult;
use App\Modules\NoorImport\DTOs\NoorRowDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Applies parsed Noor rows to the platform.
 *
 *  students            → create/update users with role=student, plus their
 *                        parent (ولي الأمر) account and the parent↔student link
 *  teachers            → create users with role=teacher
 *  admins              → create users with role=school-admin
 *  students_academic   → match by national_id, upsert academic_number
 *
 * Dedup rules (card "نظام الاستيراد نور"):
 *  - student national_id exists → UPDATE, never duplicate
 *  - parent national_id exists  → UPDATE + link to student
 *  - parent missing             → CREATE parent + link to student
 */
final class ImportNoorUsersAction
{
    /** @var array<string,int|null> in-call cache for section/class lookups */
    private array $sectionCache = [];
    private array $classCache = [];

    /**
     * @param  array<int, NoorRowDto>  $rows
     */
    public function execute(array $rows, string $type, int $schoolId): NoorImportResult
    {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $parentCreated = 0;
        $parentUpdated = 0;
        $errors = [];

        $roleSlug = match ($type) {
            'students'           => 'student',
            'students_academic'  => null,
            'teachers'           => 'teacher',
            'admins'             => 'school-admin',
            default              => null,
        };

        $role = $roleSlug ? Role::where('slug', $roleSlug)->first() : null;
        $parentRole = $type === 'students' ? Role::where('slug', 'parent')->first() : null;

        foreach ($rows as $row) {
            try {
                if (! $row->nationalId) {
                    $failed++;
                    $errors[] = ['row' => $row->rowNumber, 'reason' => __('noor.errors.missing_id')];
                    continue;
                }

                if ($type === 'students_academic') {
                    $user = User::where('national_id', $row->nationalId)->first();
                    if (! $user) {
                        $failed++;
                        $errors[] = ['row' => $row->rowNumber, 'reason' => 'No matching user for national_id ' . $row->nationalId];
                        continue;
                    }
                    DB::table('noor_user_academics')->updateOrInsert(
                        ['user_id' => $user->id],
                        ['academic_number' => $row->academicNumber, 'updated_at' => now(), 'created_at' => now()]
                    );
                    $updated++;
                    continue;
                }

                $existing = User::withTrashed()
                    ->where('national_id', $row->nationalId)
                    ->where('school_id', $schoolId)
                    ->first();

                $payload = $this->payloadFromRow($row, $schoolId);

                if ($existing) {
                    // Never touch an existing user's password on update.
                    $existing->fill($payload)->save();
                    if ($role && ! $existing->roles()->where('role_id', $role->id)->exists()) {
                        $existing->roles()->attach($role->id);
                    }
                    $student = $existing;
                    $updated++;
                } else {
                    $payload['password'] = Hash::make($row->nationalId);
                    $payload['plain_password_for_card'] = $row->nationalId;
                    $student = User::create($payload);
                    if ($role) {
                        $student->roles()->attach($role->id);
                    }
                    $created++;
                }

                // Parent handling (students only).
                if ($type === 'students' && $row->parentNationalId) {
                    $pStat = $this->upsertParent($row, $schoolId, $student, $parentRole);
                    if ($pStat === 'created') $parentCreated++;
                    elseif ($pStat === 'updated') $parentUpdated++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $row->rowNumber,
                    'reason' => $e->getMessage(),
                ];
            }
        }

        return new NoorImportResult(
            total: count($rows),
            created: $created,
            updated: $updated,
            failed: $failed,
            errors: $errors,
            status: 'completed',
            parentCreated: $parentCreated,
            parentUpdated: $parentUpdated,
        );
    }

    /**
     * Execute from the classified preview rows persisted at preview time.
     * Skips invalid + duplicate rows; runs the rest through execute().
     *
     * @param  array<int, array<string, mixed>>  $previewRows
     */
    public function executeFromPreview(array $previewRows, string $type, int $schoolId): NoorImportResult
    {
        $dtos = [];
        $skipped = 0;
        foreach ($previewRows as $pr) {
            $status = $pr['status'] ?? 'new';
            if (in_array($status, ['invalid', 'duplicate'], true)) {
                $skipped++;
                continue;
            }
            $dtos[] = NoorRowDto::fromArray($pr);
        }

        $result = $this->execute($dtos, $type, $schoolId);

        // Report the total as the full preview size, count skipped as failed
        // so the operator sees that invalid/duplicate rows were not imported.
        return new NoorImportResult(
            total: count($previewRows),
            created: $result->created,
            updated: $result->updated,
            failed: $result->failed + $skipped,
            errors: $result->errors,
            status: 'completed',
            parentCreated: $result->parentCreated,
            parentUpdated: $result->parentUpdated,
        );
    }

    /** @return string created|updated|none */
    private function upsertParent(NoorRowDto $row, int $schoolId, User $student, ?Role $parentRole): string
    {
        $pid = trim((string) $row->parentNationalId);
        if ($pid === '') return 'none';

        $parent = User::withTrashed()
            ->where('national_id', $pid)
            ->where('school_id', $schoolId)
            ->first();

        $name = $row->parentName ?: ('ولي أمر ' . $pid);
        $username = $this->safeUsername($pid);

        if ($parent) {
            // Update mutable fields only — never the password.
            $parent->fill(array_filter([
                'name'    => $row->parentName ?: $parent->name,
                'name_ar' => $row->parentName ?: $parent->name_ar,
                'phone'   => $row->parentPhone,
            ], fn ($v) => $v !== null && $v !== ''))->save();
            $status = 'updated';
        } else {
            $parent = User::create(array_filter([
                'name'                => $name,
                'name_ar'             => $name,
                'school_id'           => $schoolId,
                'national_id'         => $pid,
                'username'            => $username,
                'phone'               => $row->parentPhone,
                'email'               => $username . '@noor.local',
                'gender'              => 'male',
                'language'            => 'ar',
                'language_preference' => 'ar',
                'timezone'            => 'Asia/Riyadh',
                'status'              => 'active',
                'is_active'           => true,
                'password'            => Hash::make($pid),
                'plain_password_for_card' => $pid,
            ], fn ($v) => $v !== null && $v !== ''));
            $status = 'created';
        }

        if ($parentRole && ! $parent->roles()->where('role_id', $parentRole->id)->exists()) {
            $parent->roles()->attach($parentRole->id);
        }

        // Link parent ↔ student (idempotent via unique pivot).
        if (! DB::table('parent_student')
            ->where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->exists()) {
            DB::table('parent_student')->insert([
                'parent_id'                 => $parent->id,
                'student_id'                => $student->id,
                'relationship'              => 'parent',
                'is_primary'                => true,
                'can_receive_notifications' => true,
                'created_at'                => now(),
                'updated_at'                => now(),
            ]);
        }

        return $status;
    }

    private function payloadFromRow(NoorRowDto $row, int $schoolId): array
    {
        $name = $row->name ?: ('مستخدم ' . $row->nationalId);
        $username = $this->safeUsername((string) $row->nationalId);
        // Legacy users table has NOT NULL on email/gender/language/timezone
        // with no defaults, so fill safe placeholders rather than letting
        // the insert blow up mid-batch.
        $email = $row->email ?: ($username . '@noor.local');
        $payload = [
            'name' => $name,
            'name_ar' => $name,
            'school_id' => $schoolId,
            'national_id' => $row->nationalId,
            'username' => $username,
            'phone' => $row->phone,
            'email' => $email,
            'gender' => $this->normalizeGender($row->gender) ?? 'male',
            'birth_date' => $row->birthDate,
            'specialization' => $row->specialization,
            // Best-effort class assignment from the Noor grade/class columns.
            'section_id' => $sectionId = $this->matchSection($row->grade, $schoolId),
            'class_room_id' => $this->matchClass($row->classRoom, $sectionId),
            'language' => 'ar',
            'language_preference' => 'ar',
            'timezone' => 'Asia/Riyadh',
            'status' => $this->normalizeStatus($row->studentStatus),
            'is_active' => $this->normalizeStatus($row->studentStatus) === 'active',
        ];

        // nationality column exists on users in newer schema only — guard it.
        if ($row->nationality && \Illuminate\Support\Facades\Schema::hasColumn('users', 'nationality')) {
            $payload['nationality'] = $row->nationality;
        }

        return array_filter($payload, fn ($v) => $v !== null && $v !== '');
    }

    /** Best-effort match of a Noor grade name to a Section in the school. */
    private function matchSection(?string $grade, int $schoolId): ?int
    {
        $grade = $grade ? trim($grade) : '';
        if ($grade === '') return null;
        $key = $schoolId . '|' . $grade;
        if (array_key_exists($key, $this->sectionCache)) {
            return $this->sectionCache[$key];
        }
        $id = Section::where('school_id', $schoolId)
            ->where(function ($q) use ($grade) {
                $q->where('name', $grade)->orWhere('name', 'like', '%' . $grade . '%');
            })
            ->value('id');
        return $this->sectionCache[$key] = $id ? (int) $id : null;
    }

    /** Best-effort match of a Noor class name to a ClassRoom inside the section. */
    private function matchClass(?string $class, ?int $sectionId): ?int
    {
        $class = $class ? trim($class) : '';
        if ($class === '' || ! $sectionId) return null;
        $key = $sectionId . '|' . $class;
        if (array_key_exists($key, $this->classCache)) {
            return $this->classCache[$key];
        }
        $id = ClassRoom::where('section_id', $sectionId)
            ->where(function ($q) use ($class) {
                $q->where('name', $class)->orWhere('name', 'like', '%' . $class . '%');
            })
            ->value('id');
        return $this->classCache[$key] = $id ? (int) $id : null;
    }

    private function safeUsername(string $base): string
    {
        $base = preg_replace('/\D+/', '', $base) ?: Str::random(8);
        $candidate = $base;
        $i = 1;
        while (User::where('username', $candidate)->exists()) {
            $candidate = $base . '-' . $i++;
        }
        return $candidate;
    }

    private function normalizeGender(?string $raw): ?string
    {
        if (! $raw) return null;
        $raw = trim($raw);
        if (in_array($raw, ['ذكر', 'M', 'male', 'Male'], true)) return 'male';
        if (in_array($raw, ['أنثى', 'انثى', 'F', 'female', 'Female'], true)) return 'female';
        // Per Noor instruction: mixed schools default to male
        return 'male';
    }

    private function normalizeStatus(?string $raw): string
    {
        if (! $raw) return 'active';
        $raw = trim($raw);
        // Common Noor "not enrolled / withdrawn / transferred" wordings.
        foreach (['منقطع', 'منسحب', 'محول', 'مطوي', 'غير منتظم', 'موقوف', 'inactive', 'withdrawn'] as $needle) {
            if (mb_strpos($raw, $needle) !== false) return 'inactive';
        }
        return 'active';
    }
}
