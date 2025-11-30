<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['المرحلة الابتدائية', 'المرحلة المتوسطة', 'المرحلة الثانوية']),
            'school_id' => School::factory(),
            'is_active' => true,
        ];
    }
}
