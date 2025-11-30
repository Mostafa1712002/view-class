<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassRoomFactory extends Factory
{
    protected $model = ClassRoom::class;

    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['الصف الأول', 'الصف الثاني', 'الصف الثالث']),
            'section_id' => Section::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'grade_level' => fake()->numberBetween(1, 6),
            'division' => fake()->randomElement(['أ', 'ب', 'ج']),
            'capacity' => fake()->numberBetween(20, 40),
            'is_active' => true,
        ];
    }
}
