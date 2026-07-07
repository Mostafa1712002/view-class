<?php

namespace App\Modules\ClassPeriods\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ClassPeriod;
use App\Models\ScheduleEntry;
use App\Modules\ClassPeriods\Services\ScheduleConflictDetector;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ScheduleEntryController extends Controller
{
    use HasSchoolScope;

    public function __construct(private ScheduleConflictDetector $conflicts) {}

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();

        $data = $request->validate([
            'class_period_id' => ['required', 'integer', 'exists:class_periods,id'],
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'day_of_week' => ['required', 'integer', 'min:0', 'max:4'],
        ]);

        $period = ClassPeriod::query()->where('school_id', $schoolId)->findOrFail($data['class_period_id']);

        $reason = $this->conflicts->describeConflict($period, (int) $data['time_slot_id'], (int) $data['day_of_week']);
        if ($reason !== null) {
            return back()->with('error', __('sprint4.class_periods.flash.conflict_' . strtolower($reason)));
        }

        // Teacher weekly quota (spec: CRITICAL — max 35 periods/week). Reject any
        // assignment that would push the period's teacher past the cap.
        $teacherId = (int) $period->teacher_id;
        if ($teacherId > 0) {
            $teacherLoad = ScheduleEntry::query()
                ->where('school_id', $schoolId)
                ->whereHas('classPeriod', fn ($q) => $q->where('teacher_id', $teacherId))
                ->count();
            if ($teacherLoad >= 35) {
                return back()->with('error', __('sprint4.class_periods.flash.quota_exceeded'));
            }
        }

        ScheduleEntry::create($data + ['school_id' => $schoolId]);

        return back()->with('success', __('sprint4.class_periods.flash.entry_added'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $entry = ScheduleEntry::query()->where('school_id', $schoolId)->whereKey($id)->firstOrFail();
        $entry->delete();

        return back()->with('success', __('sprint4.class_periods.flash.entry_deleted'));
    }
}
