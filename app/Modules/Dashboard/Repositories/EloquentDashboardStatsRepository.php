<?php

namespace App\Modules\Dashboard\Repositories;

use App\Models\User;
use App\Modules\Dashboard\Repositories\Contracts\DashboardStatsRepository;

final class EloquentDashboardStatsRepository implements DashboardStatsRepository
{
    public function counts(?int $schoolId): array
    {
        $base = User::query();
        if ($schoolId) {
            $base->where('school_id', $schoolId);
        }

        $byRole = fn (string $slug) => (clone $base)
            ->whereHas('roles', fn ($q) => $q->where('slug', $slug))
            ->count();

        return [
            'studentsCount' => $byRole('student'),
            'teachersCount' => $byRole('teacher'),
            'parentsCount' => $byRole('parent'),
            'adminsCount' => $byRole('super-admin') + $byRole('school-admin') + $byRole('deputy'),
        ];
    }

    public function interactionRates(?int $schoolId): array
    {
        // Real metrics pipeline is out of scope for Sprint 1 per spec —
        // return zeros until analytics ingestion is wired.
        return [
            'studentsLoginRate' => 0,
            'teachersLoginRate' => 0,
            'parentsLoginRate' => 0,
            'studentTeacherInteraction' => 0,
            'studentContentInteraction' => 0,
        ];
    }

    public function contentStats(?int $schoolId): array
    {
        return [
            'electronicExams' => 0,
            'electronicAssignments' => 0,
            'videosFiles' => 0,
            'contentInteractionRate' => 0,
            'viewRate' => 0,
            'contentInteractions' => 0,
            'examSubmissions' => 0,
            'assignmentSubmissions' => 0,
            'smsUsage' => 0,
        ];
    }

    public function variousStats(?int $schoolId): array
    {
        return [
            'discussionRooms' => 0,
            'absences' => 0,
            'preparationPlans' => 0,
            'questionsCount' => 0,
            'virtualClasses' => 0,
            'scheduledVirtualClasses' => 0,
        ];
    }

    public function weeklyAbsenceRate(?int $schoolId): array
    {
        return [
            ['day' => 'sun', 'rate' => 0],
            ['day' => 'mon', 'rate' => 0],
            ['day' => 'tue', 'rate' => 0],
            ['day' => 'wed', 'rate' => 0],
            ['day' => 'thu', 'rate' => 0],
        ];
    }
}
