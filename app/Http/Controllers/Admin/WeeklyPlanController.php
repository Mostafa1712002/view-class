<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\User;
use App\Models\WeeklyPlan;
use Illuminate\Http\Request;
use Carbon\Carbon;

class WeeklyPlanController extends Controller
{
    protected function getSchoolId()
    {
        $user = auth()->user();
        return $user->isSuperAdmin() ? null : $user->school_id;
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $schoolId = $this->getSchoolId();

        $query = WeeklyPlan::with(['teacher', 'subject', 'classRoom.section', 'lockedByUser']);

        // Teacher sees only their plans
        if ($user->isTeacher() && !$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            $query->where('teacher_id', $user->id);
        } elseif ($schoolId) {
            // School admin sees their school's plans
            $query->whereHas('teacher', fn($q) => $q->where('school_id', $schoolId));
        }

        // Filtering
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('week_start_date')) {
            $query->where('week_start_date', $request->week_start_date);
        }

        if ($request->filled('status')) {
            if ($request->status === 'locked') {
                $query->locked();
            } elseif ($request->status === 'unlocked') {
                $query->unlocked();
            }
        }

        $plans = $query->latest('week_start_date')->paginate(15);

        // For filtering dropdowns
        $teachersQuery = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'));
        $subjectsQuery = Subject::query();
        $classesQuery = ClassRoom::with('section');

        if ($schoolId) {
            $teachersQuery->where('school_id', $schoolId);
            $subjectsQuery->where('school_id', $schoolId);
            $classesQuery->whereHas('section', fn($q) => $q->where('school_id', $schoolId));
        }

        $teachers = $teachersQuery->get();
        $subjects = $subjectsQuery->get();
        $classes = $classesQuery->get();

        return view('admin.weekly-plans.index', compact('plans', 'teachers', 'subjects', 'classes'));
    }

    public function create()
    {
        $user = auth()->user();
        $schoolId = $this->getSchoolId();

        // Teacher can only create their own plans
        $teachersQuery = User::whereHas('roles', fn($q) => $q->where('slug', 'teacher'));
        $subjectsQuery = Subject::query();
        $classesQuery = ClassRoom::with('section')->active();

        if ($user->isTeacher() && !$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            $teachers = collect([$user]);
            $subjectsQuery->whereHas('teachers', fn($q) => $q->where('users.id', $user->id));
        } else {
            if ($schoolId) {
                $teachersQuery->where('school_id', $schoolId);
                $subjectsQuery->where('school_id', $schoolId);
                $classesQuery->whereHas('section', fn($q) => $q->where('school_id', $schoolId));
            }
            $teachers = $teachersQuery->get();
        }

        $subjects = $subjectsQuery->get();
        $classes = $classesQuery->get();

        // Calculate current and next week start dates
        $currentWeekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $nextWeekStart = $currentWeekStart->copy()->addWeek();

        return view('admin.weekly-plans.create', compact('teachers', 'subjects', 'classes', 'currentWeekStart', 'nextWeekStart'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'week_start_date' => 'required|date',
            'objectives' => 'nullable|string',
            'topics' => 'nullable|string',
            'activities' => 'nullable|string',
            'resources' => 'nullable|string',
            'assessment' => 'nullable|string',
            'homework' => 'nullable|string',
            'notes' => 'nullable|string',
        ], [
            'teacher_id.required' => 'المعلم مطلوب',
            'subject_id.required' => 'المادة مطلوبة',
            'class_id.required' => 'الفصل مطلوب',
            'week_start_date.required' => 'تاريخ بداية الأسبوع مطلوب',
        ]);

        // Check permission
        if ($user->isTeacher() && !$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            if ($validated['teacher_id'] != $user->id) {
                abort(403, 'لا يمكنك إنشاء خطة لمعلم آخر');
            }
        }

        // Calculate week end date
        $startDate = Carbon::parse($validated['week_start_date']);
        $validated['week_end_date'] = $startDate->copy()->addDays(6)->format('Y-m-d');

        // Check for duplicate
        $exists = WeeklyPlan::where('teacher_id', $validated['teacher_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('class_id', $validated['class_id'])
            ->where('week_start_date', $validated['week_start_date'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'توجد خطة بنفس البيانات مسبقاً');
        }

        $plan = WeeklyPlan::create($validated);

        return redirect()->route('manage.weekly-plans.show', $plan)
            ->with('success', 'تم إنشاء الخطة الأسبوعية بنجاح');
    }

    public function show(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        if (!$this->canViewPlan($user, $weeklyPlan)) {
            abort(403);
        }

        $weeklyPlan->load(['teacher', 'subject', 'classRoom.section', 'lockedByUser']);

        return view('admin.weekly-plans.show', compact('weeklyPlan'));
    }

    public function edit(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        if (!$weeklyPlan->canEdit($user)) {
            return redirect()->route('manage.weekly-plans.show', $weeklyPlan)
                ->with('error', 'لا يمكن تعديل هذه الخطة - إما أنها مقفلة أو ليست لك');
        }

        $schoolId = $this->getSchoolId();

        $subjectsQuery = Subject::query();
        $classesQuery = ClassRoom::with('section')->active();

        if ($user->isTeacher() && !$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            $subjectsQuery->whereHas('teachers', fn($q) => $q->where('users.id', $user->id));
        } elseif ($schoolId) {
            $subjectsQuery->where('school_id', $schoolId);
            $classesQuery->whereHas('section', fn($q) => $q->where('school_id', $schoolId));
        }

        $subjects = $subjectsQuery->get();
        $classes = $classesQuery->get();

        return view('admin.weekly-plans.edit', compact('weeklyPlan', 'subjects', 'classes'));
    }

    public function update(Request $request, WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        if (!$weeklyPlan->canEdit($user)) {
            return redirect()->route('manage.weekly-plans.show', $weeklyPlan)
                ->with('error', 'لا يمكن تعديل هذه الخطة');
        }

        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'class_id' => 'required|exists:classes,id',
            'objectives' => 'nullable|string',
            'topics' => 'nullable|string',
            'activities' => 'nullable|string',
            'resources' => 'nullable|string',
            'assessment' => 'nullable|string',
            'homework' => 'nullable|string',
            'notes' => 'nullable|string',
        ], [
            'subject_id.required' => 'المادة مطلوبة',
            'class_id.required' => 'الفصل مطلوب',
        ]);

        $weeklyPlan->update($validated);

        return redirect()->route('manage.weekly-plans.show', $weeklyPlan)
            ->with('success', 'تم تحديث الخطة الأسبوعية بنجاح');
    }

    public function destroy(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        // Only admins can delete, or teacher if plan is not locked
        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            if ($weeklyPlan->teacher_id != $user->id || $weeklyPlan->is_locked) {
                abort(403);
            }
        }

        $weeklyPlan->delete();

        return redirect()->route('manage.weekly-plans.index')
            ->with('success', 'تم حذف الخطة الأسبوعية بنجاح');
    }

    public function lock(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        // Only admins can lock
        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            abort(403, 'غير مصرح لك بقفل الخطط');
        }

        if ($weeklyPlan->is_locked) {
            return back()->with('info', 'الخطة مقفلة مسبقاً');
        }

        $weeklyPlan->lock($user);

        return back()->with('success', 'تم قفل الخطة بنجاح');
    }

    public function unlock(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        // Only admins can unlock
        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            abort(403, 'غير مصرح لك بفتح قفل الخطط');
        }

        if (!$weeklyPlan->is_locked) {
            return back()->with('info', 'الخطة غير مقفلة');
        }

        $weeklyPlan->unlock();

        return back()->with('success', 'تم فتح قفل الخطة بنجاح');
    }

    public function bulkLock(Request $request)
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin() && !$user->isSchoolAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'plan_ids' => 'required|array',
            'plan_ids.*' => 'exists:weekly_plans,id',
        ]);

        $count = 0;
        foreach ($validated['plan_ids'] as $planId) {
            $plan = WeeklyPlan::find($planId);
            if ($plan && !$plan->is_locked) {
                $plan->lock($user);
                $count++;
            }
        }

        return back()->with('success', "تم قفل {$count} خطة بنجاح");
    }

    public function duplicate(WeeklyPlan $weeklyPlan)
    {
        $user = auth()->user();

        if (!$this->canViewPlan($user, $weeklyPlan)) {
            abort(403);
        }

        // Calculate next week
        $nextWeekStart = Carbon::parse($weeklyPlan->week_start_date)->addWeek();
        $nextWeekEnd = $nextWeekStart->copy()->addDays(6);

        // Check for existing plan
        $exists = WeeklyPlan::where('teacher_id', $weeklyPlan->teacher_id)
            ->where('subject_id', $weeklyPlan->subject_id)
            ->where('class_id', $weeklyPlan->class_id)
            ->where('week_start_date', $nextWeekStart->format('Y-m-d'))
            ->exists();

        if ($exists) {
            return back()->with('error', 'توجد خطة للأسبوع القادم مسبقاً');
        }

        $newPlan = $weeklyPlan->replicate();
        $newPlan->week_start_date = $nextWeekStart;
        $newPlan->week_end_date = $nextWeekEnd;
        $newPlan->is_locked = false;
        $newPlan->locked_at = null;
        $newPlan->locked_by = null;
        $newPlan->save();

        return redirect()->route('manage.weekly-plans.edit', $newPlan)
            ->with('success', 'تم نسخ الخطة للأسبوع القادم بنجاح');
    }

    protected function canViewPlan(User $user, WeeklyPlan $plan): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isSchoolAdmin()) {
            return $plan->teacher->school_id === $user->school_id;
        }

        if ($user->isTeacher()) {
            return $plan->teacher_id === $user->id;
        }

        return false;
    }
}
