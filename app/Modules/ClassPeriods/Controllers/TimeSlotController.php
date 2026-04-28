<?php

namespace App\Modules\ClassPeriods\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TimeSlot;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimeSlotController extends Controller
{
    use HasSchoolScope;

    public function index(): View
    {
        $schoolId = $this->activeSchoolId();
        $slots = TimeSlot::query()->where('school_id', $schoolId)->orderBy('period_no')->get();

        return view('admin.class-periods.time-slots', compact('slots'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();

        $data = $request->validate([
            'period_no' => ['required', 'integer', 'min:1', 'max:20'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'is_break' => ['nullable', 'boolean'],
        ]);
        $data['school_id'] = $schoolId;

        TimeSlot::updateOrCreate(
            ['school_id' => $schoolId, 'period_no' => $data['period_no']],
            $data
        );

        return redirect()
            ->route('admin.class-periods.time-slots.index')
            ->with('success', __('sprint4.class_periods.flash.slot_saved'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $schoolId = $this->activeSchoolId();
        $slot = TimeSlot::query()->where('school_id', $schoolId)->whereKey($id)->firstOrFail();
        $slot->delete();

        return redirect()
            ->route('admin.class-periods.time-slots.index')
            ->with('success', __('sprint4.class_periods.flash.slot_deleted'));
    }
}
