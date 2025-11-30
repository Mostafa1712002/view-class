<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        $quizAvg = fake()->randomFloat(2, 60, 100);
        $homeworkAvg = fake()->randomFloat(2, 60, 100);
        $midterm = fake()->randomFloat(2, 50, 100);
        $final = fake()->randomFloat(2, 50, 100);
        $participation = fake()->randomFloat(2, 70, 100);

        $total = ($quizAvg * 0.15) + ($homeworkAvg * 0.10) + ($midterm * 0.25) + ($final * 0.40) + ($participation * 0.10);

        return [
            'student_id' => User::factory(),
            'subject_id' => Subject::factory(),
            'class_id' => ClassRoom::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'teacher_id' => User::factory(),
            'semester' => fake()->randomElement(['first', 'second']),
            'quiz_avg' => $quizAvg,
            'homework_avg' => $homeworkAvg,
            'midterm' => $midterm,
            'final' => $final,
            'participation' => $participation,
            'total' => $total,
            'letter_grade' => $this->calculateLetterGrade($total),
            'comments' => fake()->optional()->sentence(),
            'is_published' => true,
        ];
    }

    private function calculateLetterGrade(float $percentage): string
    {
        return match (true) {
            $percentage >= 95 => 'A+',
            $percentage >= 90 => 'A',
            $percentage >= 85 => 'B+',
            $percentage >= 80 => 'B',
            $percentage >= 75 => 'C+',
            $percentage >= 70 => 'C',
            $percentage >= 65 => 'D+',
            $percentage >= 60 => 'D',
            default => 'F',
        };
    }
}
