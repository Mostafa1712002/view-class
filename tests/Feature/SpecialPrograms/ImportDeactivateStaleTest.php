<?php

namespace Tests\Feature\SpecialPrograms;

use App\Modules\SpecialPrograms\Actions\Import\ImportAlAwwalBank;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** A re-run deactivates (never deletes) imported items whose source question / sub-question is gone. */
class ImportDeactivateStaleTest extends TestCase
{
    private ConnectionInterface $src;

    public function refreshDatabase(): void
    {
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        // A few historical migrations are MySQL-only (ALTER ... MODIFY); none touch the import tables.
        foreach (glob(database_path('migrations/*.php')) as $path) {
            try {
                (require $path)->up();
            } catch (\Throwable $e) {
                if (! str_contains($e->getMessage(), 'MODIFY')) {
                    throw $e;
                }
            }
        }
        // The skipped MODIFYs widen enums (bank_questions.status gains 'approved'); sqlite keeps the old CHECK.
        DB::statement('PRAGMA ignore_check_constraints = ON');
    }

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('database.connections.alawwal_test', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $this->src = DB::connection('alawwal_test');
        $schema = $this->src->getSchemaBuilder();
        $schema->create('skills', fn (Blueprint $t) => [$t->id(), $t->integer('subject_id'), $t->string('name'), $t->string('term')->nullable()]);
        $schema->create('questions', function (Blueprint $t) {
            $t->id();
            $t->string('exam_type');
            $t->integer('subject_id');
            foreach (['is_independent', 'passage_text', 'question_text', 'options', 'skills', 'skill_id', 'question_image', 'difficulty', 'term', 'week', 'question_order'] as $c) {
                $t->text($c)->nullable();
            }
        });
        $schema->create('classroom_question', fn (Blueprint $t) => [$t->integer('question_id'), $t->integer('classroom_id')]);
        $schema->create('classrooms', fn (Blueprint $t) => [$t->id(), $t->string('Name_Class'), $t->integer('Grade_id')]);
        $schema->create('grades', fn (Blueprint $t) => [$t->id(), $t->integer('school_type_id')]);

        $this->src->table('skills')->insert(['id' => 1, 'subject_id' => 21, 'name' => 'تناظر', 'term' => 'first']);
        for ($i = 1; $i <= 8; $i++) {
            $this->src->table('questions')->insert($this->row($i, ["سؤال رقم {$i}"], [$this->opts(1)]));
        }
        $this->src->table('questions')->insert($this->row(100, ['فرعي أ', 'فرعي ب', 'فرعي ج'], [$this->opts(0), $this->opts(1), $this->opts(2)], true));
    }

    private function opts(int $correct): array
    {
        return array_map(fn ($t, $i) => ['text' => $t, 'is_correct' => $i === $correct], ['أ', 'ب', 'ج', 'د'], [0, 1, 2, 3]);
    }

    private function row(int $id, array $stems, array $options, bool $passage = false): array
    {
        return [
            'id' => $id, 'exam_type' => 'qiyas', 'subject_id' => 21, 'skill_id' => 1, 'difficulty' => 'easy', 'term' => 'first',
            'is_independent' => $passage ? 'passage' : 'independent', 'passage_text' => $passage ? 'نص القطعة' : null,
            'question_text' => json_encode($stems, JSON_UNESCAPED_UNICODE),
            'options' => json_encode($passage ? $options : $options[0], JSON_UNESCAPED_UNICODE),
        ];
    }

    private function import(bool $dryRun = false, bool $allowMass = false): array
    {
        return app(ImportAlAwwalBank::class)->execute($this->src, sys_get_temp_dir(), ['qiyas'], $dryRun, null, $allowMass)['qiyas'];
    }

    /** external_id => is_active */
    private function items(): array
    {
        return DB::table('sp_questions as s')->join('bank_questions as b', 'b.id', '=', 's.bank_question_id')
            ->orderBy('b.external_id')->pluck('s.is_active', 'b.external_id')->map(fn ($v) => (bool) $v)->all();
    }

    private function inactive(): array
    {
        return array_map('strval', array_keys(array_filter($this->items(), fn ($a) => ! $a)));
    }

    public function test_deleted_source_question_is_deactivated_and_undecodable_row_is_kept(): void
    {
        $first = $this->import();
        $this->assertSame(11, $first['items']['created']);
        $this->assertSame(0, $first['deactivated']);

        $this->src->table('questions')->where('id', 3)->delete();
        $this->src->table('questions')->where('id', 4)->update(['question_text' => 'not json']);
        $r = $this->import();

        $this->assertSame(1, $r['deactivated']);
        $this->assertSame(['3'], array_values($r['deactivated_items']));
        $this->assertSame(['3'], $this->inactive());
        $this->assertCount(11, $this->items(), 'nothing is deleted');
        $this->assertSame(0, $this->import()['deactivated'], 'a re-run does not count it again');
    }

    public function test_passage_that_lost_a_sub_question_deactivates_only_that_one(): void
    {
        $this->import();
        $this->src->table('questions')->where('id', 100)->update(
            $this->row(100, ['فرعي أ', 'فرعي ج'], [$this->opts(0), $this->opts(2)], true));

        $r = $this->import();

        // Sub-items are positional: dropping the middle one leaves 100:0, 100:1 (updated) and orphans 100:2.
        $this->assertSame(1, $r['deactivated']);
        $this->assertSame(['100:2'], $this->inactive());
        $this->assertTrue($this->items()['100:0'] && $this->items()['100:1']);
    }

    public function test_dry_run_reports_the_count_and_writes_nothing(): void
    {
        $this->import();
        $this->src->table('questions')->where('id', 5)->delete();

        $r = $this->import(dryRun: true);

        $this->assertSame(1, $r['deactivated']);
        $this->assertSame([], $this->inactive());
    }

    public function test_mass_deactivation_is_refused_unless_allowed(): void
    {
        $this->import();
        $this->src->table('questions')->whereIn('id', [1, 2, 3])->delete(); // 3 of 11 > 20%

        $r = $this->import();
        $this->assertSame(0, $r['deactivated']);
        $this->assertSame(3, $r['deactivate_blocked']);
        $this->assertSame([], $this->inactive());

        $r = $this->import(allowMass: true);
        $this->assertSame(3, $r['deactivated']);
        $this->assertSame(['1', '2', '3'], $this->inactive());
    }
}
