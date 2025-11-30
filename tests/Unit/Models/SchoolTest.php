<?php

namespace Tests\Unit\Models;

use App\Models\School;
use App\Models\Section;
use App\Models\User;
use Tests\TestCase;

class SchoolTest extends TestCase
{
    public function test_school_can_be_created(): void
    {
        $school = School::factory()->create([
            'name' => 'مدرسة اختبارية',
            'email' => 'school@test.com',
        ]);

        $this->assertDatabaseHas('schools', [
            'name' => 'مدرسة اختبارية',
            'email' => 'school@test.com',
        ]);
    }

    public function test_school_has_many_sections(): void
    {
        $school = School::factory()->create();
        $sections = Section::factory()->count(3)->create(['school_id' => $school->id]);

        $this->assertCount(3, $school->sections);
    }

    public function test_school_has_many_users(): void
    {
        $school = School::factory()->create();
        User::factory()->count(5)->create(['school_id' => $school->id]);

        $this->assertCount(5, $school->users);
    }

    public function test_school_is_active_by_default(): void
    {
        $school = School::factory()->create();
        $this->assertTrue($school->is_active);
    }

    public function test_school_can_be_inactive(): void
    {
        $school = School::factory()->inactive()->create();
        $this->assertFalse($school->is_active);
    }

    public function test_school_settings_is_array(): void
    {
        $school = School::factory()->create([
            'settings' => ['theme' => 'dark', 'notifications' => true],
        ]);

        $this->assertIsArray($school->settings);
        $this->assertEquals('dark', $school->settings['theme']);
    }
}
