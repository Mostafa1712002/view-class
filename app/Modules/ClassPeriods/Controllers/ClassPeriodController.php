<?php

namespace App\Modules\ClassPeriods\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassPeriod;
use App\Models\ClassRoom;
use App\Models\ScheduleEntry;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\User;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassPeriodController extends Controller
{
    use HasSchoolScope;

    public function index(Request $request): View
    {
        $schoolId = $this->activeSchoolId();

        $periods = ClassPeriod::query()
            ->with(['teacher', 'subject', 'classRoom'])
            ->where('school_id', $schoolId)
            ->when($request->get('q'), function ($q, $needle) {
                $q->whereHas('teacher', fn ($qq) => $qq->where('name', 'like', "%$needle%"))
                  ->orWhereHas('subject', fn ($qq) => $qq->where('name', 'like', "%$needle%"));
            })
            ->orderBy('grade_level')
            ->paginate(25)
            ->withQueryString();

        return view('admin.class-periods.index', compact('periods'));
    }

    public function create(): View
    {
        $schoolId = $this->activeSchoolId();
        $teachers = User::query()
            ->where('school_id', $schoolId)
            ->whereHas('roles', fn ($q) => $q->where('slug', 'teacher'))
            ->orderBy('name')
            ->get();
        $subjects = Subject::query()->where('school_id', $schoolId)->where('is_active', true)->orderBy('name')->get();
        $classes = ClassRoom::query()->where('is_active', true)->orderBy('grade_level')->orderBy('division')->get();

        return view('admin.class-periods.create', compact('teachers', 'subjects', 'classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();

        $data = $request->validate([
            'teacher_id' => ['required', 'integer', 'exists:users,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'grade_level' => ['required', 'integer', 'min:1', 'max:12'],
            'substitute_teacher_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        $data['school_id'] = $schoolId;

        ClassPeriod::create($data);

        return redirect()
            ->route('admin.class-periods.index')
            ->with('success', __('sprint4.class_periods.flash.created'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $period = ClassPeriod::query()->where('school_id', $schoolId)->whereKey($id)->firstOrFail();
        $period->delete();

        return redirect()
            ->route('admin.class-periods.index')
            ->with('success', __('sprint4.class_periods.flash.deleted'));
    }

    public function advanced(): View
    {
        $schoolId = $this->activeSchoolId();
        $periods = ClassPeriod::query()
            ->with(['teacher', 'subject', 'classRoom'])
            ->where('school_id', $schoolId)
            ->get();
        $slots = TimeSlot::query()->where('school_id', $schoolId)->orderBy('period_no')->get();
        $entries = ScheduleEntry::query()
            ->with(['classPeriod.teacher', 'classPeriod.subject', 'classPeriod.classRoom'])
            ->where('school_id', $schoolId)
            ->get()
            ->groupBy(function ($e) { return $e->day_of_week . '-' . $e->time_slot_id; });

        return view('admin.class-periods.advanced', compact('periods', 'slots', 'entries'));
    }
}
