<?php

namespace Database\Factories;

use App\Models\ClassRoom;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExamFactory extends Factory
{
    protected $model = Exam::class;

    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['اختبار الفصل الأول', 'اختبار الفصل الثاني', 'الاختبار النهائي']),
            'type' => fake()->randomElement(['quiz', 'midterm', 'final']),
            'subject_id' => Subject::factory(),
            'class_id' => ClassRoom::factory(),
            'teacher_id' => User::factory(),
            'start_time' => fake()->dateTimeBetween('now', '+1 month'),
            'end_time' => fake()->dateTimeBetween('+1 month', '+2 months'),
            'duration_minutes' => 60,
            'total_marks' => 100,
            'pass_marks' => 50,
            'description' => fake()->sentence(),
            'is_published' => true,
            'status' => 'scheduled',
        ];
    }
}
