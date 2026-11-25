<?php

namespace Tests\Feature;

use App\Models\EducationalCompany;
use App\Models\School;
use App\Modules\QuestionBankCore\Models\Compound;
use Tests\TestCase;

class ScopeLockedSchoolTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*', '2026_04_27_*',
            '2026_05_17_100001_create_school_branches*', '2026_05_17_100002_add_card51*', '2026_06_04_150000_*',
            '2026_06_14_300000_*', '2026_06_15_500001_*', '2026_07_09_030000_*', '2026_04_28_100001_*', '2026_05_18_120061_*', '2026_05_25_120000_*', '2026_07_11_*', '2026_07_25_000100_*', '2026_11_19_371*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_11_19_371001_seed_points_permissions.php'))->up();
    }

    private function fixture(): array
    {
        $co = EducationalCompany::create(['name_ar' => 'شركة أ', 'name_en' => 'A', 'status' => 'active']);
        $other = EducationalCompany::create(['name_ar' => 'شركة ب', 'name_en' => 'B', 'status' => 'active']);
        $mine = Compound::create(['educational_company_id' => $co->id, 'name_ar' => 'مجمع خاص بي']);
        $notMine = Compound::create(['educational_company_id' => $co->id, 'name_ar' => 'مجمع غريب']);
        $school = School::factory()->create(['educational_company_id' => $co->id]);
        $mine->schools()->attach($school->id);

        return compact('co', 'other', 'mine', 'notMine', 'school');
    }

    public function test_header_shows_fixed_chips_not_selects_for_a_one_school_teacher(): void
    {
        $f = $this->fixture();
        $this->actingAs($this->createSchoolAdmin($f['school']));
        $html = $this->get(route('manage.points.index'))->assertOk()->getContent();

        $this->assertStringContainsString('مجمع خاص بي', $html);
        $this->assertStringNotContainsString('مجمع غريب', $html);
        $this->assertStringNotContainsString('name="compound_id"', $html);
        $this->assertStringContainsString('name="school_id" value="'.$f['school']->id.'"', $html);
    }

    public function test_forged_post_cannot_move_a_one_school_account_off_its_school(): void
    {
        $f = $this->fixture();
        $this->actingAs($this->createSchoolAdmin($f['school']));
        $this->post(route('scope.set'), ['company_id' => $f['other']->id, 'compound_id' => $f['notMine']->id, 'school_id' => School::factory()->create()->id]);

        $this->assertSame($f['school']->id, session('scope.school_id'));
        $this->assertSame($f['co']->id, session('scope.company_id'));
        $this->assertNull(session('scope.compound_id'));
    }

    public function test_super_admin_keeps_the_free_selects_with_all_complexes(): void
    {
        $f = $this->fixture();
        $this->actingAs($this->createSuperAdmin());
        $this->post(route('scope.set'), ['company_id' => $f['co']->id]);
        $html = $this->get(route('manage.points.index'))->assertOk()->getContent();

        $this->assertStringContainsString('name="compound_id"', $html);
        $this->assertStringContainsString('مجمع غريب', $html);
    }
}
