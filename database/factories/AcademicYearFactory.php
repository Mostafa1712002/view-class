<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2023, 2025);

        return [
            'name' => "{$year}-" . ($year + 1),
            'school_id' => School::factory(),
            'start_date' => "{$year}-09-01",
            'end_date' => ($year + 1) . "-06-30",
            'is_current' => true,
        ];
    }
}
