<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\User;
use App\Modules\SpecialPrograms\Actions\Picking\NafisPicker;
use App\Modules\SpecialPrograms\Actions\Picking\PickerFor;
use App\Modules\SpecialPrograms\Actions\Picking\QiyasPicker;
use App\Modules\SpecialPrograms\Actions\Picking\TahsiliPicker;
use App\Modules\SpecialPrograms\Models\SpExamSubject;
use App\Modules\SpecialPrograms\Models\SpQuestion;
use App\Modules\SpecialPrograms\Models\SpSubject;
use App\Modules\SpecialPrograms\Repositories\EloquentQuestionPoolRepository;
use Illuminate\Support\Facades\DB;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class PickersTest extends TestCase
{
    use LoadsSpMigrations;

    private int $bq = 0;

    protected function setUp(): void
    {
        parent::setUp();
        mt_srand(42);
    }

    private function subject(string $code): SpSubject
    {
        return SpSubject::where('code', $code)->firstOrFail();
    }

    private function q(SpSubject $s, array $a = [], array $levels = []): SpQuestion
    {
        $q = SpQuestion::create($a + [
            'bank_question_id' => ++$this->bq, 'sp_subject_id' => $s->id, 'content_hash' => md5((string) $this->bq),
        ]);
        foreach ($levels as $l) {
            DB::table('sp_question_levels')->insert(['sp_question_id' => $q->id, 'grade_level' => $l]);
        }

        return $q;
    }

    private function skill(SpSubject $s, string $name = 'k'): int
    {
        return DB::table('sp_skills')->insertGetId(['sp_subject_id' => $s->id, 'name' => $name.$this->bq++,
            'created_at' => now(), 'updated_at' => now()]);
    }

    private function exam(SpSubject $s, array $a): SpExamSubject
    {
        $examId = DB::table('sp_exams')->insertGetId([
            'school_id' => 1, 'program' => $s->program, 'title' => 't', 'type' => 'single', 'expiry_hours' => 1,
            'expires_at' => now()->addDay(), 'created_by' => 1, 'creator_role' => 'admin',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $es = SpExamSubject::create($a + ['exam_id' => $examId, 'sp_subject_id' => $s->id, 'duration_minutes' => 30]);

        return $es->load('subject');
    }

    private function shares(SpExamSubject $es, array $shares): void
    {
        foreach ($shares as $skill => $pct) {
            DB::table('sp_exam_skill_shares')->insert(['exam_subject_id' => $es->id, 'sp_skill_id' => $skill, 'percentage' => $pct]);
        }
    }

    private function pick(string $cls, SpExamSubject $es): array
    {
        $pool = new EloquentQuestionPoolRepository();

        return (new $cls($pool))->pick($es, new User());
    }

    private function assertNoDuplicates(array $ids): void
    {
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_qiyas_term_includes_both_and_excludes_other_term(): void
    {
        $s = $this->subject('qiyas_quant');
        $k = $this->skill($s);
        foreach (['first' => 3, 'both' => 3, 'second' => 6] as $term => $c) {
            for ($i = 0; $i < $c; $i++) {
                $this->q($s, ['sp_skill_id' => $k, 'term' => $term]);
            }
        }
        $es = $this->exam($s, ['total_questions' => 6, 'term' => 'first']);
        $this->shares($es, [$k => 100]);
        $ids = $this->pick(QiyasPicker::class, $es);

        $this->assertCount(6, $ids);
        $this->assertNoDuplicates($ids);
        $this->assertEqualsCanonicalizing(['first', 'both'], SpQuestion::whereIn('id', $ids)->pluck('term')->unique()->all());
    }

    public function test_qiyas_weeks_filter_then_falls_back_when_short(): void
    {
        $s = $this->subject('qiyas_quant');
        $k = $this->skill($s);
        $wk1 = [];
        for ($i = 0; $i < 2; $i++) {
            $wk1[] = $this->q($s, ['sp_skill_id' => $k, 'term' => 'first', 'week' => 1])->id;
        }
        for ($i = 0; $i < 5; $i++) {
            $this->q($s, ['sp_skill_id' => $k, 'term' => 'first', 'week' => 2]);
        }
        $es = $this->exam($s, ['total_questions' => 2, 'term' => 'first', 'weeks' => [1]]);
        $this->shares($es, [$k => 100]);
        $this->assertEqualsCanonicalizing($wk1, $this->pick(QiyasPicker::class, $es));

        $es2 = $this->exam($s, ['total_questions' => 5, 'term' => 'first', 'weeks' => [1]]);
        $this->shares($es2, [$k => 100]);
        $ids = $this->pick(QiyasPicker::class, $es2);
        $this->assertCount(5, $ids);
        $this->assertNoDuplicates($ids);
        $this->assertEmpty(array_diff($wk1, $ids), 'week-1 items are kept, fallback tops up');
    }

    public function test_qiyas_passage_is_taken_whole_and_contiguous(): void
    {
        $s = $this->subject('qiyas_verbal');
        $k = $this->skill($s);
        $members = [];
        for ($i = 0; $i < 3; $i++) {
            $members[] = $this->q($s, ['sp_skill_id' => $k, 'term' => 'first', 'passage_id' => 9, 'sub_index' => $i])->id;
        }
        $this->q($s, ['sp_skill_id' => $k, 'term' => 'first']);
        $es = $this->exam($s, ['total_questions' => 1, 'term' => 'first']);
        $this->shares($es, [$k => 100]);

        for ($run = 0; $run < 8; $run++) {
            $ids = $this->pick(QiyasPicker::class, $es);
            if (count($ids) > 1) {
                $this->assertSame($members, $ids, 'sub-questions in order, contiguous');

                return;
            }
        }
        $this->fail('passage never drawn in 8 runs');
    }

    public function test_qiyas_level_filter_on_and_off(): void
    {
        $s = $this->subject('qiyas_quant');
        $k = $this->skill($s);
        $g3 = [];
        for ($i = 0; $i < 4; $i++) {
            $g3[] = $this->q($s, ['sp_skill_id' => $k, 'term' => 'both'], [3])->id;
        }
        for ($i = 0; $i < 4; $i++) {
            $this->q($s, ['sp_skill_id' => $k, 'term' => 'both'], [5]);
        }
        $on = $this->exam($s, ['total_questions' => 4, 'term' => 'first', 'question_grade_levels' => [3]]);
        $this->shares($on, [$k => 100]);
        $this->assertEqualsCanonicalizing($g3, $this->pick(QiyasPicker::class, $on));

        $off = $this->exam($s, ['total_questions' => 8, 'term' => 'first']);
        $this->shares($off, [$k => 100]);
        $this->assertCount(8, $this->pick(QiyasPicker::class, $off));
    }

    public function test_qiyas_level_drops_when_target_grade_has_fewer_than_n(): void
    {
        $s = $this->subject('qiyas_quant');
        $k = $this->skill($s);
        $own = [];
        for ($i = 0; $i < 2; $i++) {
            $own[] = $this->q($s, ['sp_skill_id' => $k, 'term' => 'first'], [10])->id;
        }
        for ($i = 0; $i < 6; $i++) {
            $this->q($s, ['sp_skill_id' => $k, 'term' => 'first'], [4]);
        }
        $es = $this->exam($s, ['total_questions' => 5, 'term' => 'first', 'question_grade_levels' => [10]]);
        $this->shares($es, [$k => 100]);
        $ids = $this->pick(QiyasPicker::class, $es);

        $this->assertCount(5, $ids);
        $this->assertNoDuplicates($ids);
        $this->assertEmpty(array_diff($own, $ids));
    }

    public function test_qiyas_default_split_over_pool_skills_when_no_shares(): void
    {
        $s = $this->subject('qiyas_quant');
        $a = $this->skill($s);
        $b = $this->skill($s);
        for ($i = 0; $i < 5; $i++) {
            $this->q($s, ['sp_skill_id' => $a, 'term' => 'first']);
            $this->q($s, ['sp_skill_id' => $b, 'term' => 'first']);
        }
        $es = $this->exam($s, ['total_questions' => 6, 'term' => 'first']);
        $ids = $this->pick(QiyasPicker::class, $es);
        $by = SpQuestion::whereIn('id', $ids)->get()->countBy('sp_skill_id');
        $this->assertSame(3, $by[$a]);
        $this->assertSame(3, $by[$b]);
    }

    public function test_nafis_reading_picks_n_passages_with_every_sub_item(): void
    {
        $s = $this->subject('nafis_reading');
        $this->assertTrue((bool) $s->picks_passages);
        foreach ([11 => 3, 12 => 2, 13 => 4, 14 => 2] as $passage => $subs) {
            for ($i = 0; $i < $subs; $i++) {
                $this->q($s, ['passage_id' => $passage, 'sub_index' => $i], [3]);
            }
        }
        $es = $this->exam($s, ['total_questions' => 2, 'question_grade_levels' => [3]]);
        $ids = $this->pick(NafisPicker::class, $es);

        $rows = SpQuestion::whereIn('id', $ids)->get()->keyBy('id');
        $passages = $rows->pluck('passage_id')->unique();
        $this->assertCount(2, $passages);
        foreach ($passages as $p) {
            $this->assertSame(SpQuestion::where('passage_id', $p)->count(), $rows->where('passage_id', $p)->count());
        }
        $seq = array_map(fn ($id) => $rows[$id]->passage_id, $ids);
        $runs = 1;
        for ($i = 1; $i < count($seq); $i++) {
            $runs += $seq[$i] !== $seq[$i - 1] ? 1 : 0;
        }
        $this->assertSame(2, $runs);
        $this->assertNoDuplicates($ids);
    }

    public function test_nafis_reading_with_no_passages_returns_empty(): void
    {
        $s = $this->subject('nafis_reading');
        $this->q($s, ['passage_id' => 1], [6]);
        $es = $this->exam($s, ['total_questions' => 2, 'question_grade_levels' => [3]]);
        $this->assertSame([], $this->pick(NafisPicker::class, $es));
    }

    public function test_nafis_math_uses_exact_skill_ids_no_term(): void
    {
        $s = $this->subject('nafis_math');
        $k4 = $this->skill($s);
        $k44 = $this->skill($s);
        $exact = [];
        for ($i = 0; $i < 3; $i++) {
            $exact[] = $this->q($s, ['sp_skill_id' => $k4])->id;
            $this->q($s, ['sp_skill_id' => $k44]);
        }
        $es = $this->exam($s, ['total_questions' => 3]);
        $this->shares($es, [$k4 => 100]);
        // k4 has exactly 3; tops up nothing else needed
        $this->assertEqualsCanonicalizing($exact, $this->pick(NafisPicker::class, $es));
    }

    public function test_tahsili_buckets_30_40_30_inside_range(): void
    {
        $s = $this->subject('tahsili_physics');
        $order = 0;
        foreach (['easy' => 20, 'medium' => 20, 'hard' => 20] as $d => $c) {
            for ($i = 0; $i < $c; $i++) {
                $this->q($s, ['difficulty' => $d, 'question_order' => ++$order]);
            }
        }
        $es = $this->exam($s, ['total_questions' => 10, 'easy_pct' => 30, 'medium_pct' => 40, 'hard_pct' => 30,
            'range_start' => 1, 'range_end' => 60]);
        $ids = $this->pick(TahsiliPicker::class, $es);

        $this->assertCount(10, $ids);
        $this->assertNoDuplicates($ids);
        $this->assertEquals(['easy' => 3, 'medium' => 4, 'hard' => 3],
            SpQuestion::whereIn('id', $ids)->get()->countBy('difficulty')->sortKeys()->only(['easy', 'medium', 'hard'])->all());
    }

    public function test_tahsili_short_range_gives_fewer_items(): void
    {
        $s = $this->subject('tahsili_math');
        foreach (['easy', 'medium', 'hard', 'easy', 'medium'] as $i => $d) {
            $this->q($s, ['difficulty' => $d, 'question_order' => $i + 1]);
        }
        $this->q($s, ['difficulty' => 'easy', 'question_order' => 99]);
        $es = $this->exam($s, ['total_questions' => 10, 'easy_pct' => 30, 'medium_pct' => 40, 'hard_pct' => 30,
            'range_start' => 1, 'range_end' => 5]);
        $ids = $this->pick(TahsiliPicker::class, $es);

        $this->assertCount(5, $ids);
        $this->assertNoDuplicates($ids);
        $this->assertSame(0, SpQuestion::whereIn('id', $ids)->where('question_order', 99)->count());
    }

    public function test_tahsili_shortfall_in_one_bucket_is_filled_from_other_difficulties(): void
    {
        $s = $this->subject('tahsili_biology');
        foreach (['easy' => 1, 'medium' => 10, 'hard' => 10] as $d => $c) {
            for ($i = 0; $i < $c; $i++) {
                $this->q($s, ['difficulty' => $d, 'question_order' => $this->bq + 1]);
            }
        }
        $es = $this->exam($s, ['total_questions' => 10, 'easy_pct' => 30, 'medium_pct' => 40, 'hard_pct' => 30]);
        $ids = $this->pick(TahsiliPicker::class, $es);
        $this->assertCount(10, $ids);
        $this->assertNoDuplicates($ids);
    }

    public function test_picker_for_resolves_per_program(): void
    {
        $this->assertInstanceOf(QiyasPicker::class, PickerFor::program('qiyas'));
        $this->assertInstanceOf(NafisPicker::class, PickerFor::program('nafis'));
        $this->assertInstanceOf(TahsiliPicker::class, PickerFor::program('tahsili'));
    }

    public function test_stats(): void
    {
        $s = $this->subject('tahsili_physics');
        $this->q($s, ['difficulty' => 'easy', 'question_order' => 1], [3]);
        $this->q($s, ['difficulty' => 'hard', 'question_order' => 7], [3, 9]);
        $st = (new EloquentQuestionPoolRepository())->stats($s->id, ['order_range' => [1, 5]]);
        $this->assertSame(2, $st['total']);
        $this->assertSame(7, (int) $st['max_order']);
        $this->assertSame(1, $st['in_range']);
        $this->assertEquals([3 => 2, 9 => 1], $st['by_level']);
    }
}
