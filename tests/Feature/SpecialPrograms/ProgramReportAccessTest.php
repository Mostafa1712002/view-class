<?php

namespace Tests\Feature\SpecialPrograms;

use App\Models\School;
use App\Modules\SpecialPrograms\Models\SpEnrollment;
use Illuminate\Support\Facades\DB;
use Tests\Feature\SpecialPrograms\Concerns\LoadsSpMigrations;
use Tests\TestCase;

class ProgramReportAccessTest extends TestCase
{
    use LoadsSpMigrations;

    private function enrol($student, string $program = 'qiyas'): void
    {
        SpEnrollment::create(['school_id' => $student->school_id, 'program' => $program, 'student_id' => $student->id, 'enrolled_by' => $student->id, 'enrolled_at' => now()]);
    }

    private function link($parent, $child): void
    {
        DB::table('parent_student')->insert(['parent_id' => $parent->id, 'student_id' => $child->id, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_student_report_page_pdf_and_unenrolled_404(): void
    {
        $school = School::factory()->create();
        $student = $this->createStudent($school);
        $this->enrol($student);

        $this->actingAs($student)->get(route('student.programs.report', 'qiyas'))->assertOk()->assertSee($student->name);
        $this->get(route('student.programs.report.pdf', 'qiyas'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('student.programs.report', 'nafis'))->assertNotFound();
    }

    public function test_parent_sees_only_own_children(): void
    {
        $school = School::factory()->create();
        $parent = $this->createParent($school);
        $mine = $this->createStudent($school);
        $mine->update(['email' => 'mine@t.com']);
        $other = $this->createStudent($school);
        $other->update(['email' => 'other@t.com']);
        $this->link($parent, $mine);
        $this->enrol($mine);
        $this->enrol($other);

        $this->actingAs($parent)->get(route('parent.programs.index'))->assertOk()->assertSee($mine->name)->assertDontSee($other->name);
        $this->get(route('parent.programs.report', [$mine->id, 'qiyas']))->assertOk();
        $this->get(route('parent.programs.report.pdf', [$mine->id, 'qiyas']))->assertOk();
        $this->get(route('parent.programs.report', [$other->id, 'qiyas']))->assertNotFound();
        $this->get(route('parent.programs.report', [$mine->id, 'tahsili']))->assertNotFound();
    }

    public function test_roles_are_separated(): void
    {
        $school = School::factory()->create();
        $student = $this->createStudent($school);
        $parent = $this->createParent($school);
        $this->actingAs($student)->get(route('parent.programs.index'))->assertForbidden();
        $this->actingAs($parent)->get(route('student.programs.report', 'qiyas'))->assertForbidden();
    }
}
