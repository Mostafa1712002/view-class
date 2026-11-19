<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\School;
use App\Models\Section;
use App\Models\User;
use App\Modules\SpecialPrograms\Models\SpEnrollment;
use App\Modules\SpecialPrograms\Repositories\Contracts\EnrollmentRepository;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use App\Modules\SpecialPrograms\Support\ProgramAudience;
use App\Modules\SpecialPrograms\Repositories\Contracts\SettingsRepository;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use LoadsSpMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantSpPermissions();
    }

    /** @return array{0: School, 1: ClassRoom, 2: User[]} */
    private function schoolWithClass(?int $grade = 3, int $students = 2): array
    {
        $school = School::factory()->create();
        $year = AcademicYear::create(['name' => 'Y', 'school_id' => $school->id, 'start_date' => now()->subMonth(), 'end_date' => now()->addMonth(), 'is_current' => true]);
        $section = Section::factory()->create(['school_id' => $school->id, 'grade_number' => $grade]);
        $class = ClassRoom::factory()->create(['section_id' => $section->id, 'academic_year_id' => $year->id, 'grade_level' => $grade ?? 0, 'name' => 'A'.uniqid(), 'division' => null]);
        $list = [];
        for ($i = 0; $i < $students; $i++) {
            $s = $this->createStudent($school);
            $s->update(['email' => uniqid().'@t.com', 'class_room_id' => $class->id]);
            $list[] = $s;
        }

        return [$school, $class, $list];
    }

    public function test_eligible_classes_follow_the_target_grades_setting(): void
    {
        [$school, $class] = $this->schoolWithClass(10);
        $this->assertFalse(ProgramAudience::classes($school->id, 'nafis')->contains('id', $class->id));
        app(SettingsRepository::class)->saveGradeLevels('nafis', [10], $this->createSuperAdmin()->id);
        $this->assertTrue(ProgramAudience::classes($school->id, 'nafis')->contains('id', $class->id));
    }

    public function test_enrol_bulk_restore_and_uniqueness(): void
    {
        [$school, $class, [$a, $b]] = $this->schoolWithClass();
        $admin = $this->createSchoolAdmin($school);
        $this->actingAs($admin)->post(route('manage.special-programs.enrollments.store', 'qiyas'), ['student_ids' => [$a->id]])->assertRedirect();
        $this->assertSame(1, SpEnrollment::count());
        // bulk resolves the class server-side; already-enrolled student is not duplicated
        $this->post(route('manage.special-programs.enrollments.store', 'qiyas'), ['all_students' => 1, 'class_id' => $class->id]);
        $this->assertSame(2, SpEnrollment::where('program', 'qiyas')->count());
        // remove then re-enrol restores the same row
        $row = SpEnrollment::where('student_id', $a->id)->first();
        $this->delete(route('manage.special-programs.enrollments.destroy', ['qiyas', $row->id]))->assertRedirect();
        $this->assertNull(SpEnrollment::find($row->id));
        $this->post(route('manage.special-programs.enrollments.store', 'qiyas'), ['student_ids' => [$a->id]]);
        $this->assertNotNull(SpEnrollment::find($row->id));
        $this->assertSame(2, SpEnrollment::withTrashed()->count());
        // same student, other program = separate row
        $this->post(route('manage.special-programs.enrollments.store', 'nafis'), ['student_ids' => [$a->id]]);
        $repo = app(EnrollmentRepository::class);
        $this->assertEqualsCanonicalizing(['qiyas', 'nafis'], $repo->programsOf($a->id));
        $this->assertTrue($repo->isEnrolled($b->id, 'qiyas'));
        $this->assertSame(2, $repo->enrolledQuery($school->id, 'qiyas')->count());
    }

    public function test_school_isolation(): void
    {
        [$school, , [$a]] = $this->schoolWithClass();
        [$other, $otherClass, [$foreign]] = $this->schoolWithClass();
        $admin = $this->createSchoolAdmin($school);
        $this->actingAs($admin);
        $this->post(route('manage.special-programs.enrollments.store', 'qiyas'), ['student_ids' => [$foreign->id]])->assertStatus(422);
        $this->post(route('manage.special-programs.enrollments.store', 'qiyas'), ['all_students' => 1, 'class_id' => $otherClass->id])->assertNotFound();
        $this->assertSame(0, SpEnrollment::count());
        $this->get(route('manage.special-programs.enrollments.create', ['qiyas', 'class_id' => $otherClass->id]))->assertOk()->assertDontSee($foreign->name);
        // foreign enrolment id cannot be removed
        $row = SpEnrollment::create(['school_id' => $other->id, 'program' => 'qiyas', 'student_id' => $foreign->id]);
        $this->delete(route('manage.special-programs.enrollments.destroy', ['qiyas', $row->id]))->assertNotFound();
        $this->assertNotNull(SpEnrollment::find($row->id));
    }

    public function test_pages_render_for_school_admin_and_super_admin_and_permissions(): void
    {
        [$school, $class] = $this->schoolWithClass();
        $admin = $this->createSchoolAdmin($school);
        $this->actingAs($admin)->get(route('manage.special-programs.index'))->assertOk()->assertSee(__('special_programs/staff.empty_bank'));
        $this->get(route('manage.special-programs.program', 'tahsili'))->assertOk();
        $this->get(route('manage.special-programs.enrollments.index', 'nafis'))->assertOk();
        $this->get(route('manage.special-programs.enrollments.create', ['nafis', 'class_id' => $class->id]))->assertOk();
        $this->get(route('manage.special-programs.program', 'bogus'))->assertNotFound();

        $this->actingAs($this->createSuperAdmin());
        foreach ([route('manage.special-programs.index'), route('manage.special-programs.program', 'qiyas'),
            route('manage.special-programs.enrollments.index', 'qiyas'), route('manage.special-programs.enrollments.create', 'qiyas')] as $url) {
            $this->get($url)->assertOk()->assertSee(__('special_programs/staff.pick_school'));
        }

        // teacher: view yes, write no; student: nothing
        $this->actingAs($this->createTeacher($school))->get(route('manage.special-programs.enrollments.index', 'qiyas'))->assertOk();
        $this->get(route('manage.special-programs.enrollments.create', 'qiyas'))->assertForbidden();
        $this->post(route('manage.special-programs.enrollments.store', 'qiyas'), [])->assertForbidden();
        $this->actingAs($this->createStudent($school))->get(route('manage.special-programs.index'))->assertForbidden();
    }

    public function test_unnumbered_grade_school_gets_alert_and_show_all_fallback(): void
    {
        [$school, $class, [$a]] = $this->schoolWithClass(null, 1);
        $admin = $this->createSchoolAdmin($school);
        $this->actingAs($admin);
        $this->get(route('manage.special-programs.program', 'qiyas'))->assertOk()
            ->assertSee('alert-warning', false)->assertSee($class->name)->assertSee('بدون رقم صف');
        $page = $this->get(route('manage.special-programs.enrollments.create', 'qiyas'))->assertOk();
        $page->assertSee('لا توجد فصول للصفوف المستهدفة');
        // class not listed by default, listed (with badge) under «عرض كل الفصول»
        $this->get(route('manage.special-programs.enrollments.create', ['qiyas', 'all' => 1]))->assertOk()
            ->assertSee($class->name)->assertSee('خارج الصفوف المستهدفة');
        $this->post(route('manage.special-programs.enrollments.store', 'qiyas'), ['all_students' => 1, 'class_id' => $class->id]);
        $this->assertTrue(app(EnrollmentRepository::class)->isEnrolled($a->id, 'qiyas'));
    }

    public function test_ineligible_numbered_grade_is_hidden_until_show_all(): void
    {
        [$school, $class] = $this->schoolWithClass(12); // qiyas = grades 1-9
        $this->actingAs($this->createSchoolAdmin($school));
        $this->get(route('manage.special-programs.enrollments.create', 'qiyas'))->assertOk()->assertDontSee($class->name);
        $this->get(route('manage.special-programs.enrollments.create', ['qiyas', 'all' => 1]))->assertOk()->assertSee($class->name);
    }
}
