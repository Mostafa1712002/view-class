<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['الرياضيات', 'اللغة العربية', 'العلوم', 'اللغة الإنجليزية', 'التربية الإسلامية']),
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'school_id' => School::factory(),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
