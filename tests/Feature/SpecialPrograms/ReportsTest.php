<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\School;
use App\Models\User;
use App\Modules\SpecialPrograms\DTOs\ReportFilters;
use App\Modules\SpecialPrograms\Repositories\Contracts\ReportRepository;
use Illuminate\Support\Facades\DB;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use LoadsSpMigrations;

    private ReportRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantSpPermissions();
        $this->repo = app(ReportRepository::class);
    }

    private function exam(School $school, string $program, int $teacherId = 1, string $role = 'admin'): array
    {
        $subjectId = (int) DB::table('sp_subjects')->where('program', $program)->orderBy('id')->value('id');
        $examId = DB::table('sp_exams')->insertGetId(['school_id' => $school->id, 'program' => $program, 'title' => 'E', 'type' => 'single',
            'expiry_hours' => 24, 'expires_at' => now()->addDay(), 'created_by' => $teacherId, 'creator_role' => $role,
            'teacher_id' => $role === 'teacher' ? $teacherId : null, 'created_at' => now(), 'updated_at' => now()]);
        $esId = DB::table('sp_exam_subjects')->insertGetId(['exam_id' => $examId, 'sp_subject_id' => $subjectId,
            'duration_minutes' => 30, 'total_questions' => 4, 'created_at' => now(), 'updated_at' => now()]);

        return [$examId, $esId, $subjectId];
    }

    /** $items: [skillId|null, difficulty, isCorrect] */
    private function attempt(School $school, string $program, int $examId, int $esId, User $student, array $items, ?string $endedAt = null): int
    {
        $correct = count(array_filter($items, fn ($i) => $i[2]));
        $id = DB::table('sp_attempts')->insertGetId(['school_id' => $school->id, 'program' => $program, 'exam_id' => $examId,
            'exam_subject_id' => $esId, 'student_id' => $student->id, 'status' => 'completed', 'started_at' => now()->subHour(),
            'deadline_at' => now(), 'ended_at' => $endedAt ?? now(), 'items_count' => count($items), 'correct_count' => $correct,
            'score_pct' => $correct * 100 / count($items), 'created_at' => now(), 'updated_at' => now()]);
        foreach ($items as $n => [$skill, $diff, $ok]) {
            DB::table('sp_attempt_items')->insert(['attempt_id' => $id, 'sp_skill_id' => $skill, 'difficulty' => $diff, 'order_index' => $n,
                'options' => '[]', 'correct_index' => 0, 'answer_index' => $ok ? 0 : 1, 'is_correct' => $ok]);
        }

        return $id;
    }

    private function skill(int $subjectId, string $name): int
    {
        return DB::table('sp_skills')->insertGetId(['sp_subject_id' => $subjectId, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_by_skill_credits_only_the_items_own_skill(): void
    {
        $school = School::factory()->create();
        $student = User::factory()->create(['school_id' => $school->id]);
        [$e, $es, $subj] = $this->exam($school, 'nafis');
        $a = $this->skill($subj, 'A');
        $b = $this->skill($subj, 'B');
        $this->attempt($school, 'nafis', $e, $es, $student, [[$a, 'easy', true], [$b, 'easy', false]]);

        $rows = $this->repo->bySkill(new ReportFilters(school: $school->id, program: 'nafis'))->keyBy('skill_name');
        $this->assertSame(1, (int) $rows['A']->answers);
        $this->assertSame(1, (int) $rows['A']->correct);
        $this->assertSame(1, (int) $rows['B']->answers);
        $this->assertSame(0, (int) $rows['B']->correct);
        $this->assertSame('ممتاز', $rows['A']->rating['name']);
        $this->assertSame('ضعيف', $rows['B']->rating['name']);
    }

    public function test_skill_rating_uses_each_programs_own_levels(): void
    {
        $school = School::factory()->create();
        $student = User::factory()->create(['school_id' => $school->id]);
        $names = [];
        foreach (['nafis', 'qiyas'] as $program) {
            [$e, $es, $subj] = $this->exam($school, $program);
            $k = $this->skill($subj, 'K');
            $this->attempt($school, $program, $e, $es, $student, [[$k, 'easy', true], [$k, 'easy', true], [$k, 'easy', false]]);
            $names[$program] = $this->repo->bySkill(new ReportFilters(school: $school->id, program: $program))->first()->rating['name'];
        }
        // 66.67%: nafis good (>=65), qiyas acceptable (60-69)
        $this->assertSame('جيد', $names['nafis']);
        $this->assertSame('مقبول', $names['qiyas']);
    }

    public function test_tahsili_difficulty_rows_are_non_zero(): void
    {
        $school = School::factory()->create();
        $student = User::factory()->create(['school_id' => $school->id]);
        [$e, $es] = $this->exam($school, 'tahsili');
        $this->attempt($school, 'tahsili', $e, $es, $student, [[null, 'easy', true], [null, 'hard', false], [null, 'hard', true]]);

        $rows = $this->repo->byDifficulty(new ReportFilters(school: $school->id, program: 'tahsili'))->keyBy('difficulty');
        $this->assertSame(1, $rows['easy']->answers);
        $this->assertSame(0, $rows['medium']->answers);
        $this->assertSame(2, $rows['hard']->answers);
        $this->assertSame(50.0, $rows['hard']->success_pct);
    }

    public function test_level_uses_the_program_bands(): void
    {
        $school = School::factory()->create();
        $student = User::factory()->create(['school_id' => $school->id]);
        // 4/5 = 80%
        foreach (['nafis' => 'very_good', 'tahsili' => 'good', 'qiyas' => 'very_good'] as $program => $expected) {
            [$e, $es] = $this->exam($school, $program);
            $this->attempt($school, $program, $e, $es, $student, array_replace(array_fill(0, 5, [null, 'easy', true]), [4 => [null, 'easy', false]]));
            $row = $this->repo->studentResults(new ReportFilters(school: $school->id, program: $program))->first();
            $this->assertSame($expected, $row->level['key'], $program);
        }
    }

    public function test_date_filter_uses_ended_at(): void
    {
        $school = School::factory()->create();
        $student = User::factory()->create(['school_id' => $school->id]);
        [$e, $es] = $this->exam($school, 'qiyas');
        $this->attempt($school, 'qiyas', $e, $es, $student, [[null, 'easy', true]], '2026-03-10 12:00:00');
        $f = fn (?string $from, ?string $to) => $this->repo->studentResults(new ReportFilters(school: $school->id, from: $from, to: $to))->count();
        $this->assertSame(1, $f('2026-03-10', '2026-03-10'));
        $this->assertSame(0, $f('2026-03-11', null));
        $this->assertSame(0, $f(null, '2026-03-09'));
    }

    public function test_teacher_sees_only_his_exams_and_school_is_isolated(): void
    {
        $school = School::factory()->create();
        $other = School::factory()->create();
        $student = User::factory()->create(['school_id' => $school->id]);
        $stranger = User::factory()->create(['school_id' => $other->id]);
        [$mine, $mineEs] = $this->exam($school, 'qiyas', 7, 'teacher');
        [$theirs, $theirsEs] = $this->exam($school, 'qiyas', 8, 'teacher');
        [$o, $oEs] = $this->exam($other, 'qiyas', 7, 'teacher');
        $this->attempt($school, 'qiyas', $mine, $mineEs, $student, [[null, 'easy', true]]);
        $this->attempt($school, 'qiyas', $theirs, $theirsEs, $student, [[null, 'easy', true]]);
        $this->attempt($other, 'qiyas', $o, $oEs, $stranger, [[null, 'easy', true]]);

        $this->assertSame(1, $this->repo->studentResults(new ReportFilters(school: $school->id, teacherId: 7))->count());
        $this->assertSame(2, $this->repo->studentResults(new ReportFilters(school: $school->id))->count());
        $this->assertSame(3, $this->repo->studentResults(new ReportFilters)->count(), 'super admin: null school = all');
        $this->assertSame([$mine], $this->repo->teacherExamSummary(7, new ReportFilters(school: $school->id))->pluck('exam_id')->all());
    }

    public function test_student_report_trend_is_ordered_by_ended_at_and_skips_open_or_deleted(): void
    {
        $school = School::factory()->create();
        $student = User::factory()->create(['school_id' => $school->id]);
        [$e, $es, $subj] = $this->exam($school, 'qiyas');
        [$e2, $es2] = $this->exam($school, 'qiyas');
        [$gone, $goneEs] = $this->exam($school, 'qiyas');
        $s = $this->skill($subj, 'S');
        $late = $this->attempt($school, 'qiyas', $e, $es, $student, [[$s, 'easy', true]], '2026-02-02 10:00:00');
        $early = $this->attempt($school, 'qiyas', $e2, $es2, $student, [[$s, 'easy', false]], '2026-01-01 10:00:00');
        $this->attempt($school, 'qiyas', $gone, $goneEs, $student, [[$s, 'easy', true]]);
        DB::table('sp_exams')->where('id', $gone)->update(['deleted_at' => now()]);

        $r = $this->repo->studentReport($student->id, 'qiyas');
        $this->assertSame([$early, $late], array_column($r['trend'], 'attempt_id'));
        $this->assertSame($late, $r['history'][0]->attempt_id);
        $this->assertSame(100.0, $r['subjects'][0]['best_pct']);
        $this->assertSame(2, $r['skills'][0]->answers);
        $this->assertArrayHasKey('level', $r['header']);
    }

    public function test_distribution_buckets_per_program(): void
    {
        $school = School::factory()->create();
        $student = User::factory()->create(['school_id' => $school->id]);
        [$e, $es] = $this->exam($school, 'tahsili');
        $this->attempt($school, 'tahsili', $e, $es, $student, [[null, 'easy', true]]); // 100
        $row = $this->repo->distribution(new ReportFilters(school: $school->id))->first();
        $this->assertSame(1, $row->bands['95-100']);
        $this->assertSame(0, $row->bands['<60']);
        $this->assertArrayHasKey('90-94', $row->bands);
    }
}
