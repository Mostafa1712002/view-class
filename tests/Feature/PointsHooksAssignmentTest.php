<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassRoom;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Modules\Gamification\Hooks\AssignmentPointHooks;
use App\Modules\Gamification\Models\PointRuleSetting;
use App\Modules\Gamification\Repositories\Contracts\PointLedgerRepository;
use Tests\TestCase;

class PointsHooksAssignmentTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_14_300000_*', '2026_07_11_*', '2026_07_25_000100_*', '2026_11_19_371*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function balance(User $u): int
    {
        return app(PointLedgerRepository::class)->balance($u->id);
    }

    private function fixture(): array
    {
        $school = School::factory()->create();
        foreach (['student.assignments' => 10, 'teacher.assignments' => 10] as $rule => $pts) {
            PointRuleSetting::create(['school_id' => 0, 'rule_key' => $rule, 'points' => $pts, 'is_enabled' => true]);
        }
        $class = ClassRoom::factory()->create();
        $teacher = User::factory()->create(['school_id' => $school->id]);
        $teacher->roles()->attach(Role::where('slug', 'teacher')->first());
        $students = [];
        foreach ([1, 2] as $i) {
            $s = User::factory()->create(['school_id' => $school->id, 'class_room_id' => $class->id, 'email' => "s$i@t.com"]);
            $s->roles()->attach(Role::where('slug', 'student')->first());
            $students[] = $s;
        }
        $a = Assignment::create(['school_id' => $school->id, 'teacher_id' => $teacher->id, 'subject_id' => \App\Models\Subject::factory()->create()->id, 'academic_year_id' => $class->academic_year_id,
            'class_id' => $class->id, 'title' => 'A', 'due_date' => now()->addDay(), 'max_score' => 20, 'status' => 'published']);

        return [$a, $teacher, $students];
    }

    public function test_grade_regrade_and_delete(): void
    {
        [$a, $teacher, [$s1, $s2]] = $this->fixture();
        $hooks = app(AssignmentPointHooks::class);

        AssignmentSubmission::create(['assignment_id' => $a->id, 'student_id' => $s1->id, 'status' => 'submitted']);
        $hooks->assignmentChanged($a, null);
        $this->assertSame(5, $this->balance($teacher));   // 1 of 2 submitted

        AssignmentSubmission::where('student_id', $s1->id)->update(['status' => 'graded', 'score' => 15]);
        $hooks->assignmentChanged($a, $s1->id);
        $this->assertSame(8, $this->balance($s1));        // 15/20 x 10 = 7.5 -> 8

        AssignmentSubmission::where('student_id', $s1->id)->update(['score' => 20]);
        $hooks->assignmentChanged($a, $s1->id);
        $hooks->assignmentChanged($a, $s1->id);           // idempotent
        $this->assertSame(10, $this->balance($s1));

        $hooks->assignmentDeleted($a->id);
        $this->assertSame(0, $this->balance($s1));
        $this->assertSame(0, $this->balance($teacher));
    }

    public function test_teacher_correct_basis_uses_average_graded_ratio(): void
    {
        [$a, $teacher, [$s1, $s2]] = $this->fixture();
        PointRuleSetting::where('rule_key', 'teacher.assignments')->update(['options' => ['basis' => 'correct']]);
        AssignmentSubmission::create(['assignment_id' => $a->id, 'student_id' => $s1->id, 'status' => 'graded', 'score' => 10]);
        AssignmentSubmission::create(['assignment_id' => $a->id, 'student_id' => $s2->id, 'status' => 'graded', 'score' => 20]);
        app(AssignmentPointHooks::class)->assignmentChanged($a, $s2->id);
        $this->assertSame(8, $this->balance($teacher));   // avg 0.75 x 10 = 7.5 -> 8
    }
}
