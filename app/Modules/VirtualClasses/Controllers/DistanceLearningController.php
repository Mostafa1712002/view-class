<?php

namespace App\Modules\VirtualClasses\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DiscussionRoom;
use App\Models\VirtualClass;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\VirtualClasses\Repositories\Contracts\VirtualClassRepositoryInterface;
use Illuminate\View\View;

class DistanceLearningController extends Controller
{
    use HasSchoolScope;

    public function index(VirtualClassRepositoryInterface $repo): View
    {
        $schoolId = $this->scopedSchoolId();
        $user = auth()->user();
        $classes = VirtualClass::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->when(! $user->hasAnyRole(['super-admin', 'school-admin']),
                fn ($q) => $q->where('teacher_id', $user->id));
        $upcomingQuery = (clone $classes)->whereIn('status', ['scheduled', 'live'])
            ->where('scheduled_at', '>=', now());
        $heldQuery = (clone $classes)->where('status', 'ended');
        $counts = [
            'total' => (clone $classes)->count(),
            'upcoming' => (clone $upcomingQuery)->count(),
            'held' => (clone $heldQuery)->count(),
            'rooms' => DiscussionRoom::where('status', 'active')
                ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))->count(),
        ];
        $upcoming = $upcomingQuery->orderBy('scheduled_at')->limit(5)->get();
        $held = $heldQuery->with('attendees')->orderByDesc('scheduled_at')->limit(5)->get();
        foreach ($held as $session) {
            // Match recalc's roster: enrolled students, or logged attendees when no roster exists.
            $roster = $repo->rosterStudentIds($session->class_id);
            $entries = $session->attendees->keyBy('student_id');
            $studentIds = $roster ?: $entries->keys()->all();
            $present = $entries->whereIn('student_id', $studentIds)
                ->whereIn('attendance_status', ['present', 'late', 'partial'])->count();
            $session->attendance_percentage = count($studentIds)
                ? round($present / count($studentIds) * 100, 1) : null;
        }

        return view('virtual-classes.manage.distance-learning', compact('counts', 'upcoming', 'held'));
    }
}
