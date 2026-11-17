<?php

namespace Tests\Feature;

use App\Models\{AcademicTerm, AcademicYear, ClassRoom, PromotionBatch, PromotionBatchItem, School, Section, StudyWeek, User};
use App\Modules\Promotion\Actions\{ExecutePromotionAction, RollbackPromotionAction};
use App\Modules\Promotion\Services\PromotionPlanner;
use App\Services\AcademicYearMigrationService;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AcademicCardsTest extends TestCase
{
    public function refreshDatabase(): void
    {
        // The full migration chain contains unrelated MySQL-only ALTER statements.
        // Use real schema migrations needed by these cards, strictly in memory.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $patterns = [
            '0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*',
            '2026_04_26_*', '2026_04_27_*',
            '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*',
            '2026_06_15_500001_qbcore_create_compounds*',
            '2026_07_09_*', '2026_07_11_*', '2026_07_25_*',
        ];
        $paths = [];
        foreach ($patterns as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    public function test_company_can_be_created_without_an_english_name(): void
    {
        $this->actingAs($this->createSuperAdmin());
        $this->post(route('admin.educational-companies.store'), ['name_ar' => 'شركة تعليمية'])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.educational-companies.index'));
        $this->assertDatabaseHas('educational_companies', ['name_ar' => 'شركة تعليمية', 'name_en' => 'شركة تعليمية']);
    }

    public function test_current_week_and_term_follow_riyadh_dates_inclusively(): void
    {
        $year = AcademicYear::factory()->create();
        $term = AcademicTerm::create(['academic_year_id' => $year->id, 'name' => 'Term',
            'start_date' => '2026-11-17', 'end_date' => '2026-11-23', 'is_current' => false]);
        $week = $term->weeks()->create(['name' => 'Week', 'start_date' => '2026-11-17', 'end_date' => '2026-11-23']);
        try {
            Carbon::setTestNow(Carbon::parse('2026-11-16 21:00:00', 'UTC'));
            $this->assertTrue($week->isCurrent());
            $this->assertTrue($term->is_current_by_date);
            $this->assertFalse($term->is_current);
            $this->assertSame($week->id, $term->currentWeek()->id);
            $this->assertSame(1, StudyWeek::current()->count());
            Carbon::setTestNow(Carbon::parse('2026-11-23 20:59:59', 'UTC'));
            $this->assertTrue($week->isCurrent());
            Carbon::setTestNow(Carbon::parse('2026-11-23 21:00:00', 'UTC'));
            $this->assertFalse($week->isCurrent());
            $this->assertFalse($term->is_current_by_date);
            $this->assertNull($term->currentWeek());
            $this->assertSame(0, StudyWeek::current()->count());
            Carbon::setTestNow(Carbon::parse('2026-11-16 20:59:59', 'UTC'));
            $this->assertFalse($week->isCurrent());
        } finally {
            Carbon::setTestNow();
        }
    }

    private function promotionFixture(): array
    {
        $actor = $this->createSuperAdmin();
        $this->actingAs($actor);
        $school = School::factory()->create();
        $src = AcademicYear::factory()->create(['school_id' => $school->id]);
        $dst = AcademicYear::factory()->create(['school_id' => $school->id]);
        $section = Section::factory()->create(['school_id' => $school->id]);
        $source = ClassRoom::factory()->create(['section_id' => $section->id, 'academic_year_id' => $src->id]);
        $destination = ClassRoom::factory()->create(['section_id' => $section->id, 'academic_year_id' => $dst->id]);
        $student = User::factory()->create(['school_id' => $school->id]);
        $source->students()->attach($student->id, ['result' => 'passed']);
        return [$school, $src, $dst, $source, $destination, $student, $actor];
    }

    public function test_migration_records_history_blocks_duplicates_and_allows_after_rollback(): void
    {
        [$school, $src, $dst, $source, $destination, $student, $actor] = $this->promotionFixture();
        $service = app(AcademicYearMigrationService::class);
        $this->assertSame(['migrated' => 1, 'skipped' => 0], $service->promoteStudents($source, $destination));
        $batch = PromotionBatch::firstOrFail();
        $this->assertSame($actor->id, $batch->executed_by);
        $this->assertNotNull($batch->executed_at);
        $this->assertSame('promoted', $batch->items->first()->action);
        $this->get(route('admin.schools.promotion.batches', $school))->assertOk()
            ->assertViewHas('batches', fn ($batches) => $batches->contains('id', $batch->id));
        $this->post(route('admin.schools.academic-years.migrate.students', $school), [
            'source_class_id' => $source->id, 'destination_class_id' => $destination->id,
        ])->assertSessionHasErrors(['destination_class_id' => __('schools.promotion_students_already_promoted', ['count' => 1])]);
        $this->assertSame(1, PromotionBatch::count());
        app(RollbackPromotionAction::class)->execute($school, $batch, 'password');
        $this->assertSame(1, $source->students()->count());
        $this->assertSame('passed', $source->students()->first()->pivot->result);
        $this->assertSame(0, $destination->students()->count());
        $this->assertSame(1, $service->promoteStudents($source, $destination)['migrated']);
    }

    public function test_preview_explains_why_nothing_would_move(): void
    {
        [$school, $src, $dst, $source, $destination, $student] = $this->promotionFixture();
        $source->section->update(['grade_number' => null, 'name' => 'Grade Without Number']);
        $source->students()->updateExistingPivot($student->id, ['result' => 'pending']);
        $destination->delete();
        $url = route('admin.schools.promotion.preview', ['school' => $school, 'source' => $src->id, 'destination' => $dst->id]);

        $this->get($url)->assertOk()
            ->assertSee(__('schools.promotion_unnumbered_grades', ['grades' => 'Grade Without Number']))
            ->assertSee(__('schools.promotion_no_destination_classes', ['year' => $dst->name]))
            ->assertSee(__('schools.promotion_no_passed', ['year' => $src->name]))
            ->assertSee(__('schools.promotion_reason_grade_unnumbered'));

        $source->section->update(['grade_number' => 1]);
        $source->update(['number' => 1]);
        $source->students()->updateExistingPivot($student->id, ['result' => 'passed']);
        $this->get($url)->assertOk()
            ->assertViewHas('readiness', fn ($r) => $r['unnumbered_grades'] === [] && $r['passed'] === 1
                && $r['classes'][0]['reason'] === 'ready');

        $this->get(route('admin.schools.promotion.preview', ['school' => $school, 'source' => $src->id, 'destination' => $src->id]))
            ->assertOk()->assertSee(__('schools.promotion_same_year'))->assertViewHas('plan', null);
    }

    public function test_execute_skips_students_promoted_by_another_source_year(): void
    {
        [$school, $src, $dst, $source, $destination, $student, $actor] = $this->promotionFixture();
        $otherYear = AcademicYear::factory()->create(['school_id' => $school->id]);
        $previous = PromotionBatch::create(['school_id' => $school->id, 'source_year_id' => $otherYear->id,
            'destination_year_id' => $dst->id, 'status' => 'executed']);
        $previous->items()->create(['student_id' => $student->id, 'action' => 'graduated']);
        $this->mock(PromotionPlanner::class, function ($mock) use ($student, $source, $destination) {
            $mock->shouldReceive('plan')->andReturn([
                'blockers' => [], 'capacity_alerts' => [],
                'summary' => ['promoted' => 1, 'graduated' => 0, 'overflow_moved' => 0, 'not_moved' => 0],
                'moves' => [['student_id' => $student->id, 'action' => 'promoted',
                    'from_section_id' => $source->section_id, 'from_class_id' => $source->id,
                    'to_section_id' => $destination->section_id, 'to_class_id' => $destination->id, 'reason' => null]],
            ]);
        });
        $batch = app(ExecutePromotionAction::class)->execute($school, $src, $dst, 'password');
        $this->assertSame('not_moved', $batch->items->first()->action);
        $this->assertSame('already promoted', $batch->items->first()->reason);
        $this->assertSame(1, $batch->summary['not_moved']);
        $this->assertSame(0, $destination->students()->count());
        $this->assertSame(1, $source->students()->count());
        $batch->update(['status' => 'rolled_back']);
        $previous->update(['status' => 'rolled_back']);
        $retry = app(ExecutePromotionAction::class)->execute($school, $src, $dst, 'password');
        $this->assertSame('promoted', $retry->items->first()->action);
        $this->assertSame(1, $destination->students()->count());
    }

    public function test_guard_includes_only_effective_moves_into_the_requested_year(): void
    {
        [$school, $src, $dst, $source, $destination, $student] = $this->promotionFixture();
        foreach (['promoted', 'graduated', 'overflow_moved', 'not_moved'] as $action) {
            $batch = PromotionBatch::create(['school_id' => $school->id, 'source_year_id' => $src->id,
                'destination_year_id' => $dst->id, 'status' => 'executed']);
            $batch->items()->create(['student_id' => $student->id, 'action' => $action]);
            $this->assertSame($action !== 'not_moved', PromotionBatchItem::movedIntoYear($dst->id)->exists());
            $this->assertFalse(PromotionBatchItem::movedIntoYear($src->id)->exists());
            $batch->update(['status' => 'rolled_back']);
            $this->assertFalse(PromotionBatchItem::movedIntoYear($dst->id)->exists());
        }
    }

    public function test_week_validation_and_badges(): void
    {
        $this->actingAs($this->createSuperAdmin());
        $year = AcademicYear::factory()->create();
        $term = $year->terms()->create(['name' => 'Term', 'start_date' => '2026-11-01', 'end_date' => '2026-11-30']);
        $url = route('admin.schools.academic-years.terms.weeks.store', [$year->school_id, $year, $term]);
        $this->post($url, ['name' => 'Bad', 'start_date' => '2026-11-02', 'end_date' => '2026-11-01'])->assertSessionHasErrors('end_date');
        $this->post($url, ['name' => 'Outside', 'start_date' => '2026-10-31', 'end_date' => '2026-11-02'])->assertSessionHasErrors('start_date');
        $this->post($url, ['name' => 'Week', 'start_date' => '2026-11-01', 'end_date' => '2026-11-07'])->assertSessionHasNoErrors();
        $this->post($url, ['name' => 'Overlap', 'start_date' => '2026-11-07', 'end_date' => '2026-11-09'])->assertSessionHasErrors('start_date');
        $this->assertSame(1, $term->weeks()->count());
        try {
            Carbon::setTestNow(Carbon::parse('2026-11-03', 'Asia/Riyadh'));
            $this->get(route('admin.schools.academic-years.index', $year->school_id))->assertOk()
                ->assertSee(__('schools.current_week'))->assertSee(__('schools.current_term'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_landings_use_school_scope_or_shared_picker_and_require_super_admin(): void
    {
        $school = School::factory()->create();
        $this->actingAs($this->createSuperAdmin());
        foreach (['admin.promotion.landing' => 'admin.schools.promotion.preview',
            'admin.terms-weeks.landing' => 'admin.schools.academic-years.index'] as $landing => $target) {
            $this->withSession(['scope.school_id' => null])->get(route($landing))->assertOk()
                ->assertSee(route($target, $school))->assertViewIs('admin.schools.academic-picker');
            $this->withSession(['scope.school_id' => $school->id])->get(route($landing))
                ->assertRedirect(route($target, $school));
        }
        $this->actingAs($this->createSchoolAdmin($school));
        $this->get(route('admin.promotion.landing'))->assertForbidden();
        $this->get(route('admin.terms-weeks.landing'))->assertForbidden();
    }
}
