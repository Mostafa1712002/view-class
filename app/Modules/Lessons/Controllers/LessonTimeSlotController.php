<?php

namespace App\Modules\Lessons\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\TimeSlot;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * إدارة الفترات الزمنية — time slots used to build the school timetable.
 * Migrated from the retired ClassPeriods module into the الحصص flow.
 */
class LessonTimeSlotController extends Controller
{
    use HasSchoolScope;

    /**
     * Time slots have a NOT NULL school_id. When a super-admin browses with
     * the "all schools" scope, activeSchoolId() is null — fall back to the
     * first school so the page never 500s (mirrors the grade-reports resolver).
     */
    protected function resolveSchoolId(): ?int
    {
        return $this->activeSchoolId() ?? School::query()->orderBy('id')->value('id');
    }

    public function index(): View
    {
        $schoolId = $this->resolveSchoolId();
        $slots = TimeSlot::query()->where('school_id', $schoolId)->orderBy('period_no')->get();

        return view('admin.lessons.time-slots', compact('slots'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->resolveSchoolId();

        $data = $request->validate([
            'period_no' => ['required', 'integer', 'min:1', 'max:20'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'is_break' => ['nullable', 'boolean'],
        ]);
        $data['school_id'] = $schoolId;
        $data['is_break'] = (bool) ($data['is_break'] ?? false);

        TimeSlot::updateOrCreate(
            ['school_id' => $schoolId, 'period_no' => $data['period_no']],
            $data
        );

        return redirect()
            ->route('admin.lessons.time-slots.index')
            ->with('success', 'تم حفظ الفترة الزمنية بنجاح');
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = $this->resolveSchoolId();
        $slot = TimeSlot::query()->where('school_id', $schoolId)->whereKey($id)->firstOrFail();
        $slot->delete();

        return redirect()
            ->route('admin.lessons.time-slots.index')
            ->with('success', 'تم حذف الفترة الزمنية بنجاح');
    }
}
