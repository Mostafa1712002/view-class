<?php

namespace Tests\Feature;

use App\Models\JobTitle;
use App\Models\School;
use App\Models\User;
use App\Modules\Counseling\Models\CounselingCase;
use Tests\TestCase;

class CounselingAccessTest extends TestCase
{
    public function refreshDatabase(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        // The full chain includes unrelated MySQL-only ALTER statements.
        $paths = [];
        foreach (['0001_01_01_000000_*', '2025_11_30_*', '2026_04_21_*', '2026_04_26_*',
            '2026_04_27_*', '2026_06_14_300000_*', '2026_11_16_372*'] as $pattern) {
            $paths = array_merge($paths, glob(database_path('migrations/'.$pattern.'.php')));
        }
        sort($paths);
        foreach ($paths as $path) {
            (require $path)->up();
        }
    }

    private function fixture(): array
    {
        $school = School::factory()->create();
        $job = JobTitle::where('slug', 'counselor')->firstOrFail();
        $a = User::factory()->create(['school_id' => $school->id, 'job_title_id' => $job->id]);
        $b = User::factory()->create(['school_id' => $school->id, 'job_title_id' => $job->id]);
        $student = $this->createStudent($school);
        $case = CounselingCase::create(['school_id' => $school->id, 'student_id' => $student->id,
            'counselor_id' => $a->id, 'category' => 'academic', 'title' => 'Confidential A',
            'summary' => 'Private summary A', 'status' => 'open', 'opened_at' => now(), 'created_by' => $a->id]);
        $other = $case->replicate();
        $other->fill(['counselor_id' => $b->id, 'title' => 'Confidential B', 'category' => 'health'])->save();

        return [$school, $a, $b, $student, $case, $other];
    }

    public function test_counselor_sees_only_assigned_cases_and_report_counts(): void
    {
        [, $a, , , $case, $other] = $this->fixture();
        $this->actingAs($a)->get(route('manage.counseling.index'))->assertOk()
            ->assertSee('Confidential A')->assertDontSee('Confidential B')
            ->assertViewHas('statusCounts', fn ($counts) => $counts['open'] === 1)
            ->assertViewHas('categoryCounts', fn ($counts) => ! isset($counts['health']));
        $this->get(route('manage.counseling.index', ['student' => 'missing']))->assertOk()->assertDontSee('Confidential A');
        $this->get(route('manage.counseling.show', $case->id))->assertOk();
        $this->get(route('manage.counseling.show', $other->id))->assertForbidden();
        $this->get(route('manage.counseling.edit', $other->id))->assertForbidden();
        $this->put(route('manage.counseling.update', $other->id), [])->assertForbidden();
        $this->post(route('manage.counseling.notes.store', $other->id), ['body' => 'secret'])->assertForbidden();
        $this->patch(route('manage.counseling.status', $other->id), ['status' => 'closed'])->assertForbidden();
        $this->delete(route('manage.counseling.destroy', $case->id))->assertForbidden();
    }

    public function test_unassigned_roles_are_denied_on_every_route(): void
    {
        [$school, , , $student, $case] = $this->fixture();
        foreach ([$this->createTeacher($school), $student, $this->createParent($school),
            User::factory()->create(['school_id' => $school->id])] as $user) {
            $this->actingAs($user);
            foreach (['index' => [], 'create' => [], 'show' => [$case->id], 'edit' => [$case->id]] as $action => $args) {
                $this->get(route('manage.counseling.'.$action, $args))->assertForbidden();
            }
            $this->post(route('manage.counseling.store'), [])->assertForbidden();
            $this->put(route('manage.counseling.update', $case->id), [])->assertForbidden();
            $this->post(route('manage.counseling.notes.store', $case->id), [])->assertForbidden();
            $this->patch(route('manage.counseling.status', $case->id), [])->assertForbidden();
            $this->delete(route('manage.counseling.destroy', $case->id))->assertForbidden();
        }
    }

    public function test_school_managers_and_super_admin_access(): void
    {
        [$school, $a, , , $case] = $this->fixture();
        $otherSchool = School::factory()->create();
        $admin = $this->createSchoolAdmin($otherSchool);
        $this->actingAs($admin)->get(route('manage.counseling.show', $case->id))->assertForbidden();
        $this->get(route('manage.counseling.index'))->assertOk()->assertDontSee('Confidential A');
        $a->update(['school_id' => $otherSchool->id]);
        $this->actingAs($a)->get(route('manage.counseling.show', $case->id))->assertForbidden();
        $admin->update(['school_id' => $school->id]);
        $this->actingAs($admin)->get(route('manage.counseling.show', $case->id))->assertOk();
        $this->actingAs($this->createSuperAdmin())->get(route('manage.counseling.show', $case->id))->assertOk();
        $this->get(route('manage.counseling.index'))->assertOk()->assertSee('Confidential A')->assertSee('Confidential B');
        $this->delete(route('manage.counseling.destroy', $case->id))->assertRedirect();
        $this->assertSoftDeleted('counseling_cases', ['id' => $case->id]);
    }

    public function test_counselor_can_create_update_note_and_close_but_cannot_change_assignment(): void
    {
        [$school, $a, $b, $student, $case] = $this->fixture();
        $data = ['student_id' => $student->id, 'counselor_id' => $a->id, 'category' => 'social',
            'title' => 'New case', 'summary' => 'Summary', 'status' => 'open'];
        $this->actingAs($a)->get(route('manage.counseling.create'))->assertOk();
        $this->post(route('manage.counseling.store'), $data)->assertRedirect();
        $this->get(route('manage.counseling.edit', $case->id))->assertOk();
        $this->put(route('manage.counseling.update', $case->id), $data)->assertRedirect();
        $this->post(route('manage.counseling.notes.store', $case->id), ['body' => 'Private note', 'author_id' => $b->id])->assertRedirect();
        $this->assertDatabaseHas('counseling_case_notes', ['case_id' => $case->id, 'author_id' => $a->id, 'body' => 'Private note']);
        $this->patch(route('manage.counseling.status', $case->id), ['status' => 'closed'])->assertRedirect();
        $this->assertNotNull($case->fresh()->closed_at);
        $this->patch(route('manage.counseling.status', $case->id), ['status' => 'open'])->assertRedirect();
        $this->assertNull($case->fresh()->closed_at);
        $data['counselor_id'] = $b->id;
        $this->put(route('manage.counseling.update', $case->id), $data)->assertForbidden();
        $data['counselor_id'] = $a->id;
        $outsider = User::factory()->create(['school_id' => School::factory()->create()->id]);
        $data['student_id'] = $outsider->id;
        $this->post(route('manage.counseling.store'), $data)->assertStatus(422);
    }

    public function test_missing_school_fails_closed_and_assignment_cannot_cross_schools(): void
    {
        [$school, $a, , $student, $case] = $this->fixture();
        $a->update(['school_id' => null]);
        $this->actingAs($a)->get(route('manage.counseling.index'))->assertForbidden();
        $this->get(route('manage.counseling.show', $case->id))->assertForbidden();
        $a->update(['school_id' => School::factory()->create()->id]);
        $admin = $this->createSchoolAdmin($school);
        $this->actingAs($admin)->post(route('manage.counseling.store'), [
            'student_id' => $student->id, 'counselor_id' => $a->id,
            'title' => 'Invalid assignment', 'summary' => 'Secret', 'category' => 'health', 'status' => 'open',
        ])->assertStatus(422);
        $this->delete(route('manage.counseling.destroy', $case->id))->assertRedirect();
        $this->assertSoftDeleted('counseling_cases', ['id' => $case->id]);
    }

    public function test_permissions_are_only_granted_to_counselor_job_titles(): void
    {
        $job = JobTitle::where('slug', 'counselor')->firstOrFail();
        $this->assertTrue($job->hasPermission('counseling.view'));
        $this->assertTrue($job->hasPermission('counseling.manage'));
        $this->assertFalse(JobTitle::where('slug', 'school_manager')->firstOrFail()->hasPermission('counseling.manage'));
    }
}
