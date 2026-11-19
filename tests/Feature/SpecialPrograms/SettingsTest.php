<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\School;
use Illuminate\Support\Facades\DB;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use LoadsSpMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantSpPermissions();
    }

    private function bands(array $over = []): array
    {
        $b = ['excellent' => [95, 100], 'very_good' => [85, 94], 'good' => [75, 84], 'acceptable' => [65, 74], 'weak' => [0, 64]];
        $b = $over + $b;

        return array_map(fn ($r) => ['min' => $r[0], 'max' => $r[1]], $b);
    }

    public function test_viewing_writes_no_row_and_school_page_is_read_only_by_default(): void
    {
        $school = School::factory()->create();
        $this->actingAs($this->createSchoolAdmin($school))
            ->get(route('manage.special-programs.settings.index', 'tahsili'))->assertOk()->assertSee(__('special_programs/settings.read_only'));
        $this->assertSame(0, DB::table('sp_settings')->count());
        $this->actingAs($this->createSuperAdmin())->get(route('manage.special-programs.settings.global', ['program' => 'nafis']))->assertOk();
        $this->assertSame(0, DB::table('sp_settings')->count());
    }

    public function test_school_edit_blocked_unless_allowed_then_overlap_rejected_and_valid_saved(): void
    {
        $school = School::factory()->create();
        $admin = $this->createSchoolAdmin($school);
        $url = route('manage.special-programs.settings.update', 'tahsili');
        $this->actingAs($admin)->put($url, ['bands' => $this->bands()])->assertForbidden();

        $this->actingAs($this->createSuperAdmin())->put(route('manage.special-programs.settings.global.update'), [
            'program' => 'tahsili', 'bands' => $this->bands(), 'grade_levels' => [3, 12], 'weeks_count' => 15, 'allow_schools' => [$school->id],
        ])->assertRedirect();
        $this->assertTrue(app(\App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository::class)->canSchoolEdit($school->id, 'tahsili'));

        $this->actingAs($admin)->put($url, ['bands' => $this->bands(['good' => [70, 84]])])->assertSessionHasErrors('bands');
        $this->put($url, ['bands' => $this->bands(['excellent' => [90, 100], 'very_good' => [80, 89], 'good' => [70, 79], 'acceptable' => [60, 69], 'weak' => [0, 59]])])
            ->assertSessionHasNoErrors();
        $row = DB::table('sp_settings')->where('school_id', $school->id)->where('program', 'tahsili')->first();
        $this->assertSame(90, json_decode($row->level_bands, true)[0]['min']);
    }

    public function test_global_save_stores_grades_and_non_super_admins_cannot_reach_global(): void
    {
        $school = School::factory()->create();
        $this->actingAs($this->createSuperAdmin())->put(route('manage.special-programs.settings.global.update'), [
            'program' => 'qiyas', 'bands' => $this->bands(), 'grade_levels' => [1, 2],
        ])->assertRedirect();
        $this->assertSame([1, 2], app(\App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository::class)->gradeLevels('qiyas'));

        $teacher = $this->createTeacher($school);
        foreach ([$this->createSchoolAdmin($school), $teacher] as $u) {
            $this->actingAs($u)->get(route('manage.special-programs.settings.global'))->assertForbidden();
            $this->put(route('manage.special-programs.settings.global.update'), ['program' => 'qiyas', 'bands' => $this->bands()])->assertForbidden();
        }
        $this->actingAs($teacher)->get(route('manage.special-programs.settings.index', 'qiyas'))->assertForbidden();
    }
}
