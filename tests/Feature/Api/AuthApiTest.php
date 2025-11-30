<?php

namespace Tests\Feature\Api;

use App\Models\School;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    public function test_user_can_login_via_api(): void
    {
        $school = School::factory()->create();
        $user = $this->createStudent($school);
        $user->update(['password' => bcrypt('password123')]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'token',
                'user',
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@test.com',
            'password' => 'wrong-password',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', [
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $school = School::factory()->create();
        $user = $this->createStudent($school);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'user',
            ]);
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_user_can_logout(): void
    {
        $school = School::factory()->create();
        $user = $this->createStudent($school);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
