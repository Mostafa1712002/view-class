<?php

namespace App\Modules\SpecialPrograms\Actions\Exams;

use App\Models\ClassRoom;
use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpExam;
use App\Modules\SpecialPrograms\Models\SpGroup;
use App\Modules\SpecialPrograms\Models\SpSkill;
use App\Modules\SpecialPrograms\Models\SpSubject;
use App\Modules\SpecialPrograms\Repositories\Contracts\GroupRepository;
use App\Modules\SpecialPrograms\Repositories\Contracts\QuestionPoolRepository;
use App\Modules\Users\Controllers\Concerns\ResolvesTeacherStudents;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** D§8: every rule is checked before the first insert; limits differ for admin and teacher. */
final class CreateProgramExam
{
    use ResolvesTeacherStudents;

    public function __construct(private QuestionPoolRepository $pool, private GroupRepository $groups) {}

    /** @param array<string,mixed> $data validated request shape (see StoreExamRequest) */
    public function execute(int $schoolId, string $program, User $user, array $data): SpExam
    {
        $teacherMode = ! $user->isSuperAdmin() && ! $user->isSchoolAdmin();
        $errors = [];
        $subjects = array_values($data['subjects'] ?? []);

        $classIds = array_values(array_unique(array_map('intval', $data['target_class_ids'] ?? [])));
        $groupIds = array_values(array_unique(array_map('intval', $data['target_group_ids'] ?? [])));
        $this->checkTargets($errors, $schoolId, $program, $user, $teacherMode, $classIds, $groupIds);

        $maxHours = $teacherMode ? 168 : 720;
        $hours = (int) ($data['expiry_hours'] ?? 0);
        if ($hours < 1 || $hours > $maxHours) {
            $errors['expiry_hours'] = __('special_programs/exams.err_expiry', ['max' => $maxHours]);
        }
        if ($teacherMode) {
            $data['type'] = 'single';
        }
        if (! $subjects || ($data['type'] === 'single' && count($subjects) !== 1)) {
            $errors['subjects'] = __('special_programs/exams.err_subject_count');
        }
        if (count(array_unique(array_column($subjects, 'sp_subject_id'))) !== count($subjects)) {
            $errors['subjects'] = __('special_programs/exams.err_subject_duplicate');
        }

        $allowed = $teacherMode ? $this->groups->teacherSubjectIds($schoolId, $user->id, $program) : null;
        foreach ($subjects as $i => $row) {
            $this->checkSubject($errors, $i, $row, $program, $schoolId, $teacherMode, $allowed);
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return DB::transaction(function () use ($schoolId, $program, $user, $teacherMode, $data, $subjects, $classIds, $groupIds, $hours) {
            $exam = SpExam::create([
                'school_id' => $schoolId, 'program' => $program, 'title' => $data['title'], 'type' => $data['type'],
                'expiry_hours' => $hours, 'expires_at' => now()->addHours($hours),
                'max_exit_attempts' => $data['max_exit_attempts'] ?? null,
                'created_by' => $user->id, 'creator_role' => $teacherMode ? 'teacher' : 'admin',
                'teacher_id' => $teacherMode ? $user->id : null,
            ]);
            foreach ($classIds as $id) {
                $exam->targets()->create(['class_id' => $id]);
            }
            foreach ($groupIds as $id) {
                $exam->targets()->create(['group_id' => $id]);
            }
            foreach ($subjects as $i => $row) {
                $es = $exam->subjects()->create([
                    'sp_subject_id' => (int) $row['sp_subject_id'],
                    'teacher_id' => $teacherMode ? $user->id : ($row['teacher_id'] ?? null),
                    'duration_minutes' => (int) $row['duration_minutes'], 'total_questions' => (int) $row['total_questions'],
                    'order_index' => (int) ($row['order_index'] ?? $i + 1),
                    'term' => $program === 'qiyas' ? $row['term'] : null,
                    'weeks' => $program === 'qiyas' ? array_values(array_map('intval', $row['weeks'])) : null,
                    'question_grade_levels' => ! empty($row['question_grade_levels']) && $program !== 'tahsili'
                        ? array_values(array_map('intval', $row['question_grade_levels'])) : null,
                    'easy_pct' => $program === 'tahsili' ? (int) $row['easy_pct'] : null,
                    'medium_pct' => $program === 'tahsili' ? (int) $row['medium_pct'] : null,
                    'hard_pct' => $program === 'tahsili' ? (int) $row['hard_pct'] : null,
                    'range_start' => $program === 'tahsili' ? ($row['range_start'] ?? null) : null,
                    'range_end' => $program === 'tahsili' ? ($row['range_end'] ?? null) : null,
                ]);
                foreach ($this->shares($row) as $skillId => $pct) {
                    $es->skillShares()->create(['sp_skill_id' => $skillId, 'percentage' => $pct]);
                }
            }

            return $exam;
        });
    }

    /** skill id => pct, blanks and zeros dropped. */
    private function shares(array $row): array
    {
        $out = [];
        foreach ($row['shares'] ?? [] as $skillId => $pct) {
            if ((int) $pct > 0) {
                $out[(int) $skillId] = (int) $pct;
            }
        }

        return $out;
    }

    private function checkTargets(array &$errors, int $schoolId, string $program, User $user, bool $teacherMode, array $classIds, array $groupIds): void
    {
        if (! $classIds && ! $groupIds) {
            $errors['target_class_ids'] = __('special_programs/exams.err_no_target');

            return;
        }
        $schoolClasses = ClassRoom::whereHas('section', fn ($q) => $q->where('school_id', $schoolId))->whereIn('id', $classIds)->pluck('id')->all();
        $schoolGroups = SpGroup::where('school_id', $schoolId)->where('program', $program)->whereIn('id', $groupIds)->pluck('id')->all();
        $okClasses = $teacherMode ? array_intersect($schoolClasses, $this->teachingClassIds($user->id)) : $schoolClasses;
        $okGroups = $teacherMode ? array_intersect($schoolGroups, $this->groups->groupIdsLedBy($user->id, $program)) : $schoolGroups;
        if (array_diff($classIds, $okClasses)) {
            $errors['target_class_ids'] = __('special_programs/exams.err_foreign_class');
        }
        if (array_diff($groupIds, $okGroups)) {
            $errors['target_group_ids'] = __('special_programs/exams.err_foreign_group');
        }
    }

    private function checkSubject(array &$errors, int $i, array $row, string $program, int $schoolId, bool $teacherMode, ?array $allowed): void
    {
        $k = fn (string $f) => "subjects.$i.$f";
        $subject = SpSubject::where('program', $program)->find((int) ($row['sp_subject_id'] ?? 0));
        if (! $subject) {
            $errors[$k('sp_subject_id')] = __('special_programs/exams.err_subject');

            return;
        }
        if ($teacherMode && $allowed !== null && ! in_array($subject->id, $allowed, true)) {
            $errors[$k('sp_subject_id')] = __('special_programs/exams.err_foreign_subject');

            return;
        }
        $available = $this->pool->stats($subject->id, [])['total'];
        if ($available < 1) {
            $errors[$k('sp_subject_id')] = __('special_programs/exams.err_empty_bank');

            return;
        }
        [$dMin, $dMax] = $teacherMode ? [15, 180] : [1, 300];
        $duration = (int) ($row['duration_minutes'] ?? 0);
        if ($duration < $dMin || $duration > $dMax) {
            $errors[$k('duration_minutes')] = __('special_programs/exams.err_duration', ['min' => $dMin, 'max' => $dMax]);
        }
        $qMax = $teacherMode ? 100 : 400;
        $n = (int) ($row['total_questions'] ?? 0);
        if ($n < 1 || $n > $qMax) {
            $errors[$k('total_questions')] = __('special_programs/exams.err_questions', ['max' => $qMax]);
        } elseif ($n > $available) {
            $errors[$k('total_questions')] = __('special_programs/exams.err_more_than_bank', ['available' => $available]);
        }
        if (! $teacherMode && ! empty($row['teacher_id'])
            && ! User::where('school_id', $schoolId)->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))->whereKey((int) $row['teacher_id'])->exists()) {
            $errors[$k('teacher_id')] = __('special_programs/exams.err_teacher');
        }

        if ($program === 'qiyas') {
            $weeks = array_filter(array_map('intval', $row['weeks'] ?? []), fn ($w) => $w >= 1 && $w <= 19);
            if (! in_array($row['term'] ?? null, ['first', 'second'], true)) {
                $errors[$k('term')] = __('special_programs/exams.err_term');
            }
            if (! $weeks || count($weeks) !== count($row['weeks'] ?? [])) {
                $errors[$k('weeks')] = __('special_programs/exams.err_weeks');
            }
        }
        if ($program === 'tahsili') {
            $sum = (int) ($row['easy_pct'] ?? 0) + (int) ($row['medium_pct'] ?? 0) + (int) ($row['hard_pct'] ?? 0);
            if ($sum !== 100) {
                $errors[$k('easy_pct')] = __('special_programs/exams.err_difficulty_sum');
            }
            $start = $row['range_start'] ?? null;
            $end = $row['range_end'] ?? null;
            if (($start === null) !== ($end === null) || ($start !== null && (int) $start > (int) $end)) {
                $errors[$k('range_start')] = __('special_programs/exams.err_range');
            }
        } else {
            $shares = $this->shares($row);
            $total = array_sum($shares);
            $own = SpSkill::where('sp_subject_id', $subject->id)->whereIn('id', array_keys($shares))->count();
            if ($shares && ($total < 1 || $total > 100 || $own !== count($shares))) {
                $errors[$k('shares')] = __('special_programs/exams.err_shares');
            }
        }
    }
}
