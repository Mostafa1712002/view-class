<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Grade;
use App\Models\Schedule;
use App\Models\WeeklyPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StudentApiController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->isStudent()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $grades = Grade::where('student_id', $user->id)
            ->with(['subject', 'exam'])
            ->latest()
            ->take(5)
            ->get();

        $attendance = Attendance::where('student_id', $user->id)
            ->whereMonth('date', Carbon::now()->month)
            ->get();

        $presentDays = $attendance->where('status', 'present')->count();
        $absentDays = $attendance->where('status', 'absent')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'grades' => $grades->map(fn($g) => [
                    'subject' => $g->subject?->name,
                    'exam' => $g->exam?->title,
                    'score' => $g->score,
                    'max_score' => $g->max_score,
                    'percentage' => $g->percentage,
                    'date' => $g->created_at->format('Y/m/d'),
                ]),
                'attendance_summary' => [
                    'present' => $presentDays,
                    'absent' => $absentDays,
                    'total' => $attendance->count(),
                ],
            ],
        ]);
    }

    public function grades(Request $request)
    {
        $user = $request->user();

        if (!$user->isStudent()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $grades = Grade::where('student_id', $user->id)
            ->with(['subject', 'exam'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $grades->map(fn($g) => [
                'id' => $g->id,
                'subject' => $g->subject?->name,
                'exam' => $g->exam?->title,
                'exam_type' => $g->exam?->type,
                'score' => $g->score,
                'max_score' => $g->max_score,
                'percentage' => $g->percentage,
                'grade_letter' => $g->grade_letter,
                'date' => $g->created_at->format('Y/m/d'),
            ]),
        ]);
    }

    public function attendance(Request $request)
    {
        $user = $request->user();

        if (!$user->isStudent()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        $attendance = Attendance::where('student_id', $user->id)
            ->whereMonth('date', $month)
            ->whereYear('date', $year)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attendance->map(fn($a) => [
                'date' => $a->date->format('Y/m/d'),
                'day' => $a->date->locale('ar')->dayName,
                'status' => $a->status,
                'status_label' => match($a->status) {
                    'present' => 'حاضر',
                    'absent' => 'غائب',
                    'late' => 'متأخر',
                    'excused' => 'معذور',
                    default => $a->status,
                },
                'notes' => $a->notes,
            ]),
        ]);
    }

    public function schedule(Request $request)
    {
        $user = $request->user();

        if (!$user->isStudent()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $schedule = Schedule::where('class_room_id', $user->class_room_id)
            ->with(['subject', 'teacher', 'periods'])
            ->get();

        $days = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'];
        $result = [];

        foreach ($days as $index => $dayName) {
            $daySchedule = [];
            foreach ($schedule as $item) {
                foreach ($item->periods->where('day', $index) as $period) {
                    $daySchedule[] = [
                        'subject' => $item->subject?->name,
                        'teacher' => $item->teacher?->name,
                        'start_time' => $period->start_time,
                        'end_time' => $period->end_time,
                    ];
                }
            }
            usort($daySchedule, fn($a, $b) => $a['start_time'] <=> $b['start_time']);
            $result[$dayName] = $daySchedule;
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function weeklyPlans(Request $request)
    {
        $user = $request->user();

        if (!$user->isStudent()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح'], 403);
        }

        $plans = WeeklyPlan::where('class_room_id', $user->class_room_id)
            ->with(['subject', 'teacher'])
            ->where('week_start', '>=', Carbon::now()->subWeeks(2))
            ->orderBy('week_start', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans->map(fn($p) => [
                'id' => $p->id,
                'subject' => $p->subject?->name,
                'teacher' => $p->teacher?->name,
                'week_start' => $p->week_start->format('Y/m/d'),
                'week_end' => $p->week_end->format('Y/m/d'),
                'topics' => $p->topics,
                'objectives' => $p->objectives,
                'resources' => $p->resources,
                'notes' => $p->notes,
            ]),
        ]);
    }
}
