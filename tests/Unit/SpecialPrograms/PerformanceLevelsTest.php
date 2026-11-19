<?php

namespace Tests\Unit\SpecialPrograms;

use App\Models\School;
use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use App\Modules\SpecialPrograms\Repositories\EloquentSettingsRepository;
use App\Modules\SpecialPrograms\Support\PerformanceLevels;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class PerformanceLevelsTest extends TestCase
{
    use LoadsSpMigrations;

    private SettingsRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantSpPermissions();
        $this->repo = new EloquentSettingsRepository();
    }

    private function level(float $pct, string $program, ?int $school = null): string
    {
        return PerformanceLevels::for($pct, $this->repo->bandsFor($school, $program))['name'];
    }

    public function test_defaults_per_program(): void
    {
        $this->assertSame('ممتاز', $this->level(95, 'tahsili'));
        $this->assertSame('جيد جداً', $this->level(94.99, 'tahsili'));
        $this->assertSame('ضعيف', $this->level(64.99, 'tahsili'));
        $this->assertSame('مقبول', $this->level(65, 'tahsili'));
        $this->assertSame('ممتاز', $this->level(85, 'nafis'));
        $this->assertSame('ضعيف', $this->level(49.99, 'nafis'));
        $this->assertSame('ممتاز', $this->level(90, 'qiyas'));
        $this->assertSame('ضعيف', $this->level(0, 'qiyas'));
    }

    public function test_school_override_beats_global_and_reads_never_insert(): void
    {
        $school = School::factory()->create();
        $this->assertSame('جيد', $this->level(75, 'tahsili', $school->id));
        $this->assertDatabaseCount('sp_settings', 0);

        $global = PerformanceLevels::defaultBands('tahsili');
        $global[0]['min'] = 90;
        $global[1]['max'] = 89;
        $this->repo->saveBands(null, 'tahsili', $global, 1);
        $this->assertSame('ممتاز', $this->level(90, 'tahsili', $school->id));

        $mine = PerformanceLevels::defaultBands('tahsili');
        $mine[0]['min'] = 99;
        $mine[1]['max'] = 98;
        $this->repo->saveBands($school->id, 'tahsili', $mine, 1);
        $this->assertSame('جيد جداً', $this->level(95, 'tahsili', $school->id));
        $this->assertSame('ممتاز', $this->level(95, 'tahsili'));
        $this->assertDatabaseCount('sp_settings', 2); // global row updated in place, not duplicated
    }

    public function test_validate_and_allow_flag(): void
    {
        $this->assertNull(PerformanceLevels::validate(PerformanceLevels::defaultBands('nafis')));
        $bad = PerformanceLevels::defaultBands('nafis');
        $bad[2]['max'] = 80; // overlap
        $this->assertNotNull(PerformanceLevels::validate($bad));
        $this->assertNotNull(PerformanceLevels::validate(array_slice(PerformanceLevels::defaultBands('nafis'), 1)));

        $school = School::factory()->create();
        $this->assertFalse($this->repo->canSchoolEdit($school->id, 'nafis'));
        $this->repo->setAllowSchoolEdit($school->id, 'nafis', true, 1);
        $this->assertTrue($this->repo->canSchoolEdit($school->id, 'nafis'));
        $this->assertSame([3, 6], $this->repo->gradeLevels('nafis'));
        $this->assertSame(19, $this->repo->weeksCount($school->id));
    }
}
