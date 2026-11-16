<?php

namespace App\Modules\Dashboard\Repositories;

use App\Modules\Dashboard\Repositories\Contracts\DashboardStatsRepository;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EloquentDashboardStatsRepository implements DashboardStatsRepository
{
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

    private function percent(int $count, int $total): int
    {
        return $total ? (int) round(100 * $count / $total) : 0;
    }

    private function since(): CarbonImmutable
    {
        return CarbonImmutable::now()->subDays(30);
    }

    private function days(): array
    {
        $sunday = CarbonImmutable::now('Asia/Riyadh')->startOfWeek(CarbonImmutable::SUNDAY);

        return array_map(fn ($i) => $sunday->addDays($i), range(0, 4));
    }

    private function onDay(Builder $query, CarbonImmutable $day, string $column): Builder
    {
        return $query->where($column, '>=', $day->setTimezone(config('app.timezone')))
            ->where($column, '<', $day->addDay()->setTimezone(config('app.timezone')));
    }

    private function users(?int $schoolId, string $role): ?Builder
    {
        if (! $this->has('users', 'roles', 'role_user')) {
            return null;
        }

        return DB::table('users')->whereNull('users.deleted_at')
            ->when($schoolId !== null, fn ($q) => $q->where('users.school_id', $schoolId))
            ->whereIn('users.id', DB::table('role_user')->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('roles.slug', $role)->select('role_user.user_id'));
    }

    private function scoped(string $table, ?int $schoolId): ?Builder
    {
        if (! $this->has($table)) {
            return null;
        }
        $q = DB::table($table);
        if (in_array($table, ['discussion_rooms', 'discussion_comments', 'subject_contents', 'virtual_classes', 'bank_questions'])) {
            $q->whereNull("$table.deleted_at");
        }
        if ($schoolId === null) {
            return $q;
        }
        if (in_array($table, ['exams', 'attendances', 'weekly_plans'])) {
            if (! $this->has('classes', 'sections')) {
                return null;
            }

            return $q->whereIn("$table.class_id", DB::table('classes')->join('sections', 'sections.id', '=', 'classes.section_id')
                ->where('sections.school_id', $schoolId)->select('classes.id'));
        }
        if ($table === 'bank_questions') {
            return $this->has('question_banks') ? $q->whereIn('question_bank_id', DB::table('question_banks')->where('school_id', $schoolId)->whereNull('deleted_at')->select('id')) : null;
        }
        if (in_array($table, ['student_exams', 'assignment_submissions'])) {
            $parent = $table === 'student_exams' ? 'exams' : 'assignments';
            $key = $table === 'student_exams' ? 'exam_id' : 'assignment_id';
            $parents = $this->scoped($parent, $schoolId);

            return $parents ? $q->whereIn($key, $parents->select("$parent.id")) : null;
        }

        return $q->where("$table.school_id", $schoolId);
    }

    public function counts(?int $schoolId): array
    {
        return [
            'studentsCount' => $this->users($schoolId, 'student')?->count() ?? 0,
            'teachersCount' => $this->users($schoolId, 'teacher')?->count() ?? 0,
            'parentsCount' => $this->users($schoolId, 'parent')?->count() ?? 0,
            'adminsCount' => array_sum(array_map(fn ($role) => $this->users($schoolId, $role)?->count() ?? 0, ['super-admin', 'school-admin', 'deputy'])),
        ];
    }

    public function interactionRates(?int $schoolId): array
    {
        $rates = [];
        foreach (['students' => 'student', 'teachers' => 'teacher', 'parents' => 'parent'] as $key => $role) {
            $users = $this->users($schoolId, $role);
            $total = $users?->count() ?? 0;
            $rates[$key.'LoginRate'] = $this->percent($users?->where('last_login_at', '>=', $this->since())->count() ?? 0, $total);
        }
        $students = $this->users($schoolId, 'student');
        $total = $students?->count() ?? 0;
        $sources = [];
        foreach (['assignment_submissions' => 'student_id', 'student_exams' => 'student_id', 'discussion_comments' => 'user_id'] as $table => $column) {
            if ($q = $this->scoped($table, $schoolId)) {
                $sources[] = [$column, $q->where('created_at', '>=', $this->since())];
            }
        }
        $participating = $students && $sources ? (clone $students)->where(function ($q) use ($sources) {
            foreach ($sources as [$column, $source]) {
                $q->orWhereIn('users.id', $source->select($column));
            }
        })->count() : 0;
        $logs = $this->scoped('activity_logs', $schoolId);
        $active = $students && $logs ? $students->whereIn('users.id', $logs->where('created_at', '>=', $this->since())->select('user_id'))->count() : 0;

        return $rates + ['studentTeacherInteraction' => $this->percent($participating, $total), 'studentContentInteraction' => $this->percent($active, $total)];
    }

    public function contentStats(?int $schoolId): array
    {
        $contents = $this->scoped('subject_contents', $schoolId);
        $total = $contents?->count() ?? 0;

        return [
            'electronicExams' => $this->scoped('exams', $schoolId)?->count() ?? 0,
            'electronicAssignments' => $this->scoped('assignments', $schoolId)?->count() ?? 0,
            'videosFiles' => $total,
            // Participation among students; viewed items among all content items.
            'contentInteractionRate' => $this->interactionRates($schoolId)['studentContentInteraction'],
            'viewRate' => $this->percent($contents?->where('views_count', '>', 0)->count() ?? 0, $total),
            'contentInteractions' => $this->scoped('activity_logs', $schoolId)?->where('created_at', '>=', $this->since())->count() ?? 0,
            'examSubmissions' => $this->scoped('student_exams', $schoolId)?->whereNotNull('submitted_at')->count() ?? 0,
            'assignmentSubmissions' => $this->scoped('assignment_submissions', $schoolId)?->count() ?? 0,
            'smsUsage' => $this->scoped('sms_messages', $schoolId)?->count() ?? 0,
        ];
    }

    public function variousStats(?int $schoolId): array
    {
        return [
            'discussionRooms' => $this->scoped('discussion_rooms', $schoolId)?->count() ?? 0,
            'absences' => $this->scoped('attendances', $schoolId)?->where('status', 'absent')->where('date', '>=', $this->since()->toDateString())->count() ?? 0,
            'preparationPlans' => $this->scoped('weekly_plans', $schoolId)?->count() ?? 0,
            'questionsCount' => $this->scoped('bank_questions', $schoolId)?->count() ?? 0,
            'virtualClasses' => $this->scoped('virtual_classes', $schoolId)?->count() ?? 0,
            'scheduledVirtualClasses' => $this->scoped('virtual_classes', $schoolId)?->where('scheduled_at', '>', now())->count() ?? 0,
        ];
    }

    public function weeklyAbsenceRate(?int $schoolId): array
    {
        return array_map(function ($day) use ($schoolId) {
            $q = $this->scoped('attendances', $schoolId)?->where('date', $day->toDateString());
            $total = $q?->count() ?? 0;

            return ['day' => strtolower($day->format('D')), 'rate' => $this->percent($q?->where('status', 'absent')->count() ?? 0, $total)];
        }, $this->days());
    }

    private function ranking(?int $schoolId, ?int $companyId, bool $classes): array
    {
        if (! $this->has('activity_logs', 'users') || ($companyId !== null && ! $this->has('schools'))
            || ($classes && ! $this->has('classes', 'sections', 'class_student'))) {
            return [];
        }
        $q = DB::table('activity_logs as logs')->join('users', 'users.id', '=', 'logs.user_id')
            ->whereNull('users.deleted_at')->where('logs.created_at', '>=', $this->since())
            ->when($schoolId !== null, fn ($q) => $q->where('logs.school_id', $schoolId)->where('users.school_id', $schoolId));
        if ($companyId !== null) {
            $q->whereIn('logs.school_id', DB::table('schools')->where('educational_company_id', $companyId)->select('id'));
        }
        $entity = 'users';
        if ($classes) {
            // Attribute student activity to their enrolled classes, never across schools.
            $q->join('class_student', 'class_student.student_id', '=', 'users.id')
                ->join('classes', 'classes.id', '=', 'class_student.class_id')
                ->join('sections', 'sections.id', '=', 'classes.section_id')
                ->whereColumn('sections.school_id', 'logs.school_id');
            $entity = 'classes';
        } else {
            $q->whereColumn('users.school_id', 'logs.school_id');
        }

        return $q->select("$entity.name")->selectRaw('COUNT(DISTINCT logs.id) as score')
            ->groupBy("$entity.id", "$entity.name")->orderByDesc('score')->orderBy("$entity.id")->limit(5)
            ->get()->map(fn ($row) => ['name' => $row->name, 'score' => (int) $row->score])->all();
    }

    public function mostActive(?int $schoolId, ?int $companyId): array
    {
        return [
            'activeClassesInSchool' => $this->ranking($schoolId, null, true),
            'activeUsersInSchool' => $this->ranking($schoolId, null, false),
            'activeClassesInCompany' => $this->ranking($companyId !== null ? null : $schoolId, $companyId, true),
            'activeUsersInCompany' => $this->ranking($companyId !== null ? null : $schoolId, $companyId, false),
        ];
    }

    public function weeklyActivity(?int $schoolId): array
    {
        $series = array_map(fn ($day) => ['day' => strtolower($day->format('D'))], $this->days());
        $rates = [];
        foreach (['parents' => 'parent', 'students' => 'student', 'teachers' => 'teacher'] as $key => $role) {
            $users = $this->users($schoolId, $role);
            $total = $users?->count() ?? 0;
            foreach ($this->days() as $i => $day) {
                $logs = $this->scoped('activity_logs', $schoolId);
                $active = $users && $logs ? (clone $users)->whereIn('users.id', $this->onDay($logs, $day, 'created_at')->select('user_id'))->count() : 0;
                $series[$i][$key] = $this->percent($active, $total);
            }
            $rates[$key.'Rate'] = (int) round(array_sum(array_column($series, $key)) / 5);
        }

        return $rates + ['series' => $series];
    }
}
