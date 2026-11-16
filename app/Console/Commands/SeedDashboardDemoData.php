<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SeedDashboardDemoData extends Command
{
    protected $signature = 'vc:demo-data {--school= : Required school ID} {--remove : Remove previously seeded data} {--force : Allow production use}';

    protected $description = 'Seed removable dashboard activity for one existing school';

    private array $tables = [];

    private function has(string ...$tables): bool
    {
        foreach ($tables as $table) {
            if (! ($this->tables[$table] ??= Schema::hasTable($table))) {
                return false;
            }
        }

        return true;
    }

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Production requires --force.');

            return self::FAILURE;
        }
        $schoolId = filter_var($this->option('school'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (! $schoolId || ! $this->has('schools') || ! DB::table('schools')->where('id', $schoolId)->exists()) {
            $this->error('Provide --school=<existing school ID>.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists(storage_path('app'));
        $path = storage_path("app/demo-data-$schoolId.json");
        $lock = fopen($path.'.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            $this->error('Another demo-data operation is running for this school.');

            return self::FAILURE;
        }
        try {
            $previous = File::exists($path) ? json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR) : [];
            if ($previous && ($previous['school_id'] ?? null) !== $schoolId) {
                throw new \RuntimeException('Demo inventory does not match the requested school.');
            }
            $manifest = ['school_id' => $schoolId, 'rows' => [], 'logins' => []];
            DB::transaction(function () use ($schoolId, $previous, &$manifest, $path) {
                $this->removeRows($schoolId, $previous);
                if (! $this->option('remove')) {
                    $this->seedRows($schoolId, $manifest);
                }
                // Persist the deletion inventory before committing any inserted rows.
                File::replace($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            });
            if ($this->option('remove')) {
                File::delete($path);
            }
            $this->info($this->option('remove') ? 'Demo data removed.' : "Demo data seeded for school $schoolId. Inventory: $path");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            // The transaction rolled back; retain the original inventory for retry.
            if (isset($previous)) {
                File::replace($path, json_encode($previous, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            }
            $this->error($e->getMessage());

            return self::FAILURE;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function removeRows(int $schoolId, array $manifest): void
    {
        foreach (['student_exams', 'assignment_submissions', 'attendances'] as $table) {
            if ($this->has($table)) {
                DB::table($table)->whereIn('id', $manifest['rows'][$table] ?? [])->delete();
            }
        }
        if ($this->has('activity_logs')) {
            DB::table('activity_logs')->where('school_id', $schoolId)->where('action', 'demo_seed')->delete();
        }
        if ($this->has('users')) {
            foreach ($manifest['logins'] ?? [] as $login) {
                // Preserve a genuine login that happened after seeding.
                DB::table('users')->where('id', $login['id'])->where('school_id', $schoolId)
                    ->where('last_login_at', $login['seeded'])->update(['last_login_at' => $login['original']]);
            }
        }
    }

    private function insert(string $table, array $row, array &$manifest): void
    {
        $manifest['rows'][$table][] = DB::table($table)->insertGetId($row);
    }

    private function seedRows(int $schoolId, array &$manifest): void
    {
        if (! $this->has('users', 'roles', 'role_user')) {
            throw new \RuntimeException('Users and role tables are required.');
        }
        $now = CarbonImmutable::now();
        $users = DB::table('users')->where('school_id', $schoolId)->whereNull('deleted_at')->get();
        foreach ($users as $i => $user) {
            $login = $now->subHours($i % 72)->toDateTimeString();
            $manifest['logins'][] = ['id' => $user->id, 'original' => $user->last_login_at, 'seeded' => $login];
            DB::table('users')->where('id', $user->id)->update(['last_login_at' => $login]);
            if ($this->has('activity_logs')) {
                for ($days = 0; $days < 14; $days++) {
                    $date = $now->subDays($days);
                    if (in_array($date->dayOfWeek, [5, 6]) || ($i + $days) % 4 === 0) {
                        continue;
                    }
                    $this->insert('activity_logs', [
                        'user_id' => $user->id, 'school_id' => $schoolId, 'action' => 'demo_seed',
                        'description' => '[demo] Learning activity', 'created_at' => $date, 'updated_at' => $date,
                    ], $manifest);
                }
            }
        }
        $roleIds = fn ($role) => DB::table('role_user')->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->where('roles.slug', $role)->whereIn('role_user.user_id', $users->pluck('id'))->pluck('role_user.user_id');
        $students = $roleIds('student');
        $teacher = $roleIds('teacher')->first();
        if (! $this->has('classes', 'sections', 'class_student')) {
            $this->warn('No class enrollment tables; skipped attendance and submissions.');

            return;
        }
        $classes = DB::table('classes')->join('sections', 'sections.id', '=', 'classes.section_id')
            ->where('sections.school_id', $schoolId)->select('classes.*')->get();
        foreach ($classes as $class) {
            $enrolled = DB::table('class_student')->where('class_id', $class->id)->whereIn('student_id', $students)->pluck('student_id');
            if ($teacher && $this->has('attendances')) {
                foreach ($enrolled as $i => $student) {
                    for ($days = 0; $days < 14; $days++) {
                        $date = $now->subDays($days);
                        if (in_array($date->dayOfWeek, [5, 6])) {
                            continue;
                        }
                        $key = ['student_id' => $student, 'class_id' => $class->id, 'date' => $date->toDateString()];
                        if (DB::table('attendances')->where($key)->exists()) {
                            continue;
                        }
                        $this->insert('attendances', $key + [
                            'teacher_id' => $teacher, 'academic_year_id' => $class->academic_year_id,
                            'status' => ($i + $days) % 5 === 0 ? 'absent' : 'present', 'notes' => '[demo]',
                            'created_at' => $date, 'updated_at' => $date,
                        ], $manifest);
                    }
                }
            }
            foreach (['exams' => 'student_exams', 'assignments' => 'assignment_submissions'] as $parent => $table) {
                if (! $this->has($parent, $table)) {
                    continue;
                }
                $parents = DB::table($parent)->where('class_id', $class->id)
                    ->when($parent === 'assignments', fn ($q) => $q->where('school_id', $schoolId))->limit(3)->get();
                foreach ($parents as $item) {
                    foreach ($enrolled->take(5) as $student) {
                        $key = [$parent === 'exams' ? 'exam_id' : 'assignment_id' => $item->id, 'student_id' => $student];
                        if (DB::table($table)->where($key)->exists()) {
                            continue;
                        }
                        $date = $now->subDays(2);
                        $this->insert($table, $key + [
                            'status' => 'submitted', 'submitted_at' => $date, 'created_at' => $date, 'updated_at' => $date,
                        ], $manifest);
                    }
                }
            }
        }
        if (! $teacher) {
            $this->warn('No existing teacher in this school; skipped attendance.');
        }
    }
}
