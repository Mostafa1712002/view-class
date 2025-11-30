<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => fake()->randomElement(['grade_published', 'attendance_alert', 'exam_scheduled', 'announcement']),
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'color' => fake()->randomElement(['primary', 'success', 'warning', 'danger', 'info']),
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
