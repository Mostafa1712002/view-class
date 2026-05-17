<?php

namespace App\Modules\NoorImport\Actions;

use App\Models\Role;
use App\Models\User;
use App\Modules\NoorImport\DTOs\NoorImportResult;
use App\Modules\NoorImport\DTOs\NoorRowDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Card 58 — applies parsed Noor rows to the platform.
 *
 *  students            → create users with role=student
 *  teachers            → create users with role=teacher
 *  admins              → create users with role=school-admin
 *  students_academic   → match by national_id, upsert academic_number
 *                        in noor_user_academics (User.php is locked).
 */
final class ImportNoorUsersAction
{
    /**
     * @param  array<int, NoorRowDto>  $rows
     */
    public function execute(array $rows, string $type, int $schoolId): NoorImportResult
    {
        $created = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        $roleSlug = match ($type) {
            'students'           => 'student',
            'students_academic'  => null,
            'teachers'           => 'teacher',
            'admins'             => 'school-admin',
            default              => null,
        };

        $role = $roleSlug ? Role::where('slug', $roleSlug)->first() : null;

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
                    $existing->fill($payload)->save();
                    if ($role && ! $existing->roles()->where('role_id', $role->id)->exists()) {
                        $existing->roles()->attach($role->id);
                    }
                    $updated++;
                } else {
                    $payload['password'] = Hash::make(Str::random(12));
                    $payload['plain_password_for_card'] = '123456';
                    $user = User::create($payload);
                    if ($role) {
                        $user->roles()->attach($role->id);
                    }
                    $created++;
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
        );
    }

    private function payloadFromRow(NoorRowDto $row, int $schoolId): array
    {
        $name = $row->name ?: ('مستخدم ' . $row->nationalId);
        return array_filter([
            'name' => $name,
            'name_ar' => $name,
            'school_id' => $schoolId,
            'national_id' => $row->nationalId,
            'username' => $this->safeUsername($row),
            'phone' => $row->phone,
            'email' => $row->email,
            'gender' => $this->normalizeGender($row->gender),
            'birth_date' => $row->birthDate,
            'specialization' => $row->specialization,
            'is_active' => true,
        ], fn ($v) => $v !== null && $v !== '');
    }

    private function safeUsername(NoorRowDto $row): string
    {
        $base = preg_replace('/\D+/', '', (string) $row->nationalId) ?: Str::random(8);
        // Avoid clashes by suffixing if already taken
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
}
