<?php

namespace Tests\Feature\Admin;

use App\Models\School;
use App\Models\Subject;
use Tests\TestCase;

class SubjectManagementTest extends TestCase
{
    public function test_admin_can_view_subjects_list(): void
    {
        $school = School::factory()->create();
        $user = $this->createSchoolAdmin($school);
        Subject::factory()->count(5)->create(['school_id' => $school->id]);

        $response = $this->actingAs($user)->get('/manage/subjects');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_subject(): void
    {
        $school = School::factory()->create();
        $user = $this->createSchoolAdmin($school);

        $response = $this->actingAs($user)->get('/manage/subjects/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_store_subject(): void
    {
        $school = School::factory()->create();
        $user = $this->createSchoolAdmin($school);

        $response = $this->actingAs($user)->post('/manage/subjects', [
            'name' => 'الرياضيات',
            'code' => 'MATH101',
            'description' => 'مادة الرياضيات',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('subjects', ['name' => 'الرياضيات']);
    }

    public function test_admin_can_delete_subject(): void
    {
        $school = School::factory()->create();
        $user = $this->createSchoolAdmin($school);
        $subject = Subject::factory()->create(['school_id' => $school->id]);

        $response = $this->actingAs($user)->delete("/manage/subjects/{$subject->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }
}
