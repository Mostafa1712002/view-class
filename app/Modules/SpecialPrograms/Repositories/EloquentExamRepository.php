<?php

namespace App\Modules\SpecialPrograms\Repositories;

use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpEnrollment;
use App\Modules\SpecialPrograms\Models\SpExam;
use App\Modules\SpecialPrograms\Repositories\Contracts\ExamRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EloquentExamRepository implements ExamRepository
{
    /** Super-admin: everything; school-admin: own + header-scoped school; teacher: own school, exams he created or teaches. */
    public function visibleTo(User $user): Builder
    {
        $q = SpExam::query();
        if ($user->isSuperAdmin()) {
            return $q;
        }
        if ($user->isSchoolAdmin()) {
            // multi-school admins: the header scope selector may point at another managed school
            $scoped = (int) session('scope.school_id');
            $ids = [(int) $user->school_id];
            if ($scoped && in_array($scoped, $user->managedSchoolIds(), true)) {
                $ids[] = $scoped;
            }

            return $q->whereIn('school_id', $ids);
        }

        return $q->where('school_id', (int) $user->school_id)->where(function ($w) use ($user) {
            $w->where('created_by', $user->id)->orWhere('teacher_id', $user->id)
                ->orWhereIn('id', DB::table('sp_exam_subjects')->where('teacher_id', $user->id)->select('exam_id'));
        });
    }

    public function findForUser(int $id, User $user): SpExam
    {
        return $this->visibleTo($user)->with(['targets', 'subjects.subject', 'subjects.skillShares.skill'])->findOrFail($id);
    }

    public function forStudent(User $student): Collection
    {
        $enrollments = SpEnrollment::where('student_id', $student->id)->get();
        if ($enrollments->isEmpty()) {
            return collect();
        }
        $classIds = $student->enrolledClassIds();

        return SpExam::query()->with(['subjects.subject'])->where(function ($q) use ($enrollments, $classIds) {
            foreach ($enrollments as $e) {
                $q->orWhere(fn ($w) => $w->where('school_id', $e->school_id)->where('program', $e->program)
                    ->whereIn('id', DB::table('sp_exam_targets')->select('exam_id')->where(function ($t) use ($e, $classIds) {
                        $t->whereIn('class_id', $classIds ?: [0]);
                        if ($e->group_id) {
                            $t->orWhere('group_id', $e->group_id);
                        }
                    })));
            }
        })->orderByDesc('id')->get();
    }

    /** Enrolled (not removed) students of the exam's school/program in a target class or target group. */
    public function audienceQuery(SpExam $exam): Builder
    {
        $targets = DB::table('sp_exam_targets')->where('exam_id', $exam->id)->get();
        $classIds = $targets->pluck('class_id')->filter()->map(fn ($v) => (int) $v)->all();
        $groupIds = $targets->pluck('group_id')->filter()->map(fn ($v) => (int) $v)->all();
        $enrolled = fn () => DB::table('sp_enrollments')->whereNull('deleted_at')
            ->where('school_id', $exam->school_id)->where('program', $exam->program);

        return User::query()->whereIn('users.id', $enrolled()->select('student_id'))
            ->where(function ($w) use ($classIds, $groupIds, $enrolled) {
                $w->whereRaw('1 = 0');
                if ($classIds) {
                    $w->orWhereIn('users.class_room_id', $classIds)
                        ->orWhereIn('users.id', DB::table('class_student')->whereIn('class_id', $classIds)->select('student_id'));
                }
                if ($groupIds) {
                    $w->orWhereIn('users.id', $enrolled()->whereIn('group_id', $groupIds)->select('student_id'));
                }
            });
    }

    public function isInAudience(SpExam $exam, User $student): bool
    {
        return $this->audienceQuery($exam)->where('users.id', $student->id)->exists();
    }
}
