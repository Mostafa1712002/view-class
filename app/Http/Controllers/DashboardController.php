<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\DiscussionRoom;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\LibraryItem;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\VirtualClass;
use App\Models\WeeklyPlan;
use App\Modules\Dashboard\Actions\GetContentStatsAction;
use App\Modules\Dashboard\Actions\GetInteractionRatesAction;
use App\Modules\Dashboard\Actions\GetMostActiveAction;
use App\Modules\Dashboard\Actions\GetWeeklyActivityAction;
use App\Modules\Dashboard\Repositories\Contracts\DashboardStatsRepository;
use App\Modules\Mail\Repositories\Contracts\MailboxRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(
        GetInteractionRatesAction $interactionRates,
        GetContentStatsAction $contentStats,
        GetMostActiveAction $mostActive,
        GetWeeklyActivityAction $weeklyActivity,
        DashboardStatsRepository $statsRepo,
        MailboxRepository $mailbox,
    ) {
        $user = Auth::user();
        $data = [];

        if ($user->isStudent()) {
            return redirect()->route('student.dashboard');
        } elseif ($user->isParent()) {
            return redirect()->route('parent.dashboard');
        } elseif ($user->isSuperAdmin()) {
            $data = $this->getSuperAdminStats();
        } elseif ($user->isSchoolAdmin()) {
            $data = $this->getSchoolAdminStats($user);
        } elseif ($user->isTeacher()) {
            $data = $this->getTeacherStats($user, $mailbox);
        }

        // Sprint 1 QA round 2 — sections 2-7 are rendered from the same repository the API uses.
        $schoolId = $user->school_id;
        $companyId = optional($user->school)->educational_company_id;

        $data['interactionRates'] = $interactionRates->execute($schoolId);
        $data['contentStatsData'] = $contentStats->execute($schoolId);
        $data['variousStatsData'] = $statsRepo->variousStats($schoolId);
        $data['weeklyAbsence'] = $statsRepo->weeklyAbsenceRate($schoolId);
        $data['mostActive'] = $mostActive->execute($schoolId, $companyId);
        $data['weeklyActivity'] = $weeklyActivity->execute($schoolId);

        return view('dashboard', $data);
    }

    private function getSuperAdminStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        return [
            'schools_count' => School::count(),
            'sections_count' => Section::count(),
            'classes_count' => ClassRoom::count(),
            'teachers_count' => User::whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))->count(),
            'students_count' => User::whereHas('roles', fn ($q) => $q->where('slug', 'student'))->count(),
            'parents_count' => User::whereHas('roles', fn ($q) => $q->where('slug', 'parent'))->count(),
            'subjects_count' => Subject::count(),
            'recent_schools' => School::latest()->take(5)->get(),
            'new_users_this_month' => User::where('created_at', '>=', $thisMonth)->count(),
            'new_users_last_month' => User::whereBetween('created_at', [$lastMonth, $thisMonth])->count(),
            'active_schools' => School::where('is_active', true)->count(),
        ];
    }

    private function getSchoolAdminStats(User $user): array
    {
        $schoolId = $user->school_id;
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();

        $studentsCount = User::where('school_id', $schoolId)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))->count();

        $todayAttendance = Attendance::whereHas('student', fn ($q) => $q->where('school_id', $schoolId))
            ->whereDate('date', $today)
            ->get();

        $presentToday = $todayAttendance->where('status', 'present')->count();
        $absentToday = $todayAttendance->where('status', 'absent')->count();

        $upcomingExams = Exam::whereHas('classRoom.section', fn ($q) => $q->where('school_id', $schoolId))
            ->where('start_time', '>=', $today)
            ->where('is_published', true)
            ->orderBy('start_time')
            ->take(5)
            ->get();

        $recentGrades = Grade::whereHas('student', fn ($q) => $q->where('school_id', $schoolId))
            ->with(['student', 'subject', 'exam'])
            ->latest()
            ->take(10)
            ->get();

        $pendingAssignments = Assignment::where('school_id', $schoolId)
            ->where('due_date', '>=', $today)
            ->where('status', 'published')
            ->count();

        $weeklyPlansThisWeek = WeeklyPlan::whereHas('teacher', fn ($q) => $q->where('school_id', $schoolId))
            ->where('week_start_date', '>=', $thisWeek)
            ->count();

        $attendanceStats = $this->getAttendanceStats($schoolId, $thisMonth);

        $gradeDistribution = Grade::whereHas('student', fn ($q) => $q->where('school_id', $schoolId))
            ->whereNotNull('total')
            ->selectRaw('
                CASE
                    WHEN total >= 90 THEN "ممتاز"
                    WHEN total >= 80 THEN "جيد جداً"
                    WHEN total >= 70 THEN "جيد"
                    WHEN total >= 60 THEN "مقبول"
                    ELSE "ضعيف"
                END as grade_level,
                COUNT(*) as count
            ')
            ->groupBy('grade_level')
            ->pluck('count', 'grade_level')
            ->toArray();

        return [
            'sections_count' => Section::where('school_id', $schoolId)->count(),
            'classes_count' => ClassRoom::whereHas('section', fn ($q) => $q->where('school_id', $schoolId))->count(),
            'teachers_count' => User::where('school_id', $schoolId)
                ->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))->count(),
            'students_count' => $studentsCount,
            'subjects_count' => Subject::where('school_id', $schoolId)->count(),
            'present_today' => $presentToday,
            'absent_today' => $absentToday,
            'attendance_rate' => $studentsCount > 0 ? round(($presentToday / max($studentsCount, 1)) * 100, 1) : 0,
            'upcoming_exams' => $upcomingExams,
            'recent_grades' => $recentGrades,
            'pending_assignments' => $pendingAssignments,
            'weekly_plans_count' => $weeklyPlansThisWeek,
            'attendance_stats' => $attendanceStats,
            'grade_distribution' => $gradeDistribution,
            'recent_notifications' => Notification::whereHas('user', fn ($q) => $q->where('school_id', $schoolId))
                ->latest()
                ->take(5)
                ->get(),
        ];
    }

    private function getTeacherStats(User $user, MailboxRepository $mailbox): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $schoolId = $user->school_id;

        // schedules has no teacher_id; teacher lives on schedule_periods. Resolve
        // a teacher's class schedules for today via the pivot.
        $todaySchedules = Schedule::whereHas('periods', fn ($q) => $q
            ->where('teacher_id', $user->id)
            ->where('day_of_week', $today->dayOfWeek))
            ->with(['classRoom', 'periods' => fn ($q) => $q->where('teacher_id', $user->id)])
            ->get();

        $mySubjects = $user->subjects()->get();

        $upcomingExams = Exam::where('teacher_id', $user->id)
            ->where('start_time', '>=', $today)
            ->orderBy('start_time')
            ->take(5)
            ->get();

        $myWeeklyPlans = WeeklyPlan::where('teacher_id', $user->id)
            ->where('week_start_date', '>=', $thisWeek)
            ->take(5)
            ->get();

        $myAssignments = Assignment::where('teacher_id', $user->id)
            ->where('status', 'published')
            ->withCount('submissions')
            ->orderBy('due_date')
            ->take(5)
            ->get();

        // Card #294 — "رئيسية المعلم" summary grid.
        $submissionsToGrade = AssignmentSubmission::where('status', 'submitted')
            ->whereHas('assignment', fn ($q) => $q->where('teacher_id', $user->id))
            ->count();

        $assignmentsPublishedCount = Assignment::where('teacher_id', $user->id)
            ->where('status', 'published')
            ->count();

        $libraryItemsCount = LibraryItem::where('school_id', $schoolId)
            ->where('is_public', true)
            ->count();

        $mailboxUnreadCount = $mailbox->getFolderCounts($user->id)['unreadInbox'] ?? 0;

        $virtualClassesUpcomingCount = VirtualClass::where('teacher_id', $user->id)
            ->whereIn('status', ['scheduled', 'live'])
            ->where('scheduled_at', '>=', $today)
            ->count();

        $discussionRoomsCount = DiscussionRoom::forSchool($schoolId)
            ->where('status', 'active')
            ->count();

        $supportTicketsOpenCount = SupportTicket::where('school_id', $schoolId)
            ->where('created_by', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->count();

        return [
            'subjects_count' => $mySubjects->count(),
            'today_schedules' => $todaySchedules,
            'upcoming_exams' => $upcomingExams,
            'weekly_plans' => $myWeeklyPlans,
            'assignments' => $myAssignments,
            'my_subjects' => $mySubjects,
            'submissions_to_grade_count' => $submissionsToGrade,
            'assignments_published_count' => $assignmentsPublishedCount,
            'library_items_count' => $libraryItemsCount,
            'mailbox_unread_count' => $mailboxUnreadCount,
            'virtual_classes_upcoming_count' => $virtualClassesUpcomingCount,
            'discussion_rooms_count' => $discussionRoomsCount,
            'support_tickets_open_count' => $supportTicketsOpenCount,
        ];
    }

    private function getAttendanceStats($schoolId, $startDate): array
    {
        $stats = [];
        $endDate = Carbon::now();

        for ($date = $startDate->copy(); $date <= $endDate; $date->addDay()) {
            if ($date->isWeekend()) {
                continue;
            }

            $dayAttendance = Attendance::whereHas('student', fn ($q) => $q->where('school_id', $schoolId))
                ->whereDate('date', $date)
                ->get();

            $stats[] = [
                'date' => $date->format('m/d'),
                'present' => $dayAttendance->where('status', 'present')->count(),
                'absent' => $dayAttendance->where('status', 'absent')->count(),
                'late' => $dayAttendance->where('status', 'late')->count(),
            ];
        }

        return $stats;
    }
}
