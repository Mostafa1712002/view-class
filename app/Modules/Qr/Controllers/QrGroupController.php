<?php

namespace App\Modules\Qr\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\Qr\Models\QrAttendanceGroup;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * #265 — QR attendance groups (time windows that turn scans into statuses).
 */
class QrGroupController extends Controller
{
    use HasSchoolScope;

    public function index(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();

        $groups = QrAttendanceGroup::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->when($request->filled('title'), fn ($q) => $q->where('title', 'like', '%'.$request->title.'%'))
            ->when($request->filled('status') && $request->status !== '', fn ($q) => $q->where('is_active', (bool) $request->status))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.qr.groups.index', compact('groups'));
    }

    public function create(): View
    {
        $this->scopedSchoolId();

        return view('admin.qr.groups.form', ['group' => new QrAttendanceGroup()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $data = $this->validateData($request);
        $data['school_id'] = $schoolId; // null only for super-admin

        $group = QrAttendanceGroup::create($data);
        ActivityLog::logCreate($group, "إنشاء مجموعة حضور QR: {$group->title}");

        return redirect()->route('admin.qr.groups.index')->with('success', 'تم إنشاء المجموعة.');
    }

    public function edit(QrAttendanceGroup $group): View
    {
        $this->guard($group);

        return view('admin.qr.groups.form', compact('group'));
    }

    public function update(Request $request, QrAttendanceGroup $group): RedirectResponse
    {
        $this->guard($group);
        $old = $group->toArray();
        $group->update($this->validateData($request));
        ActivityLog::logUpdate($group, "تعديل مجموعة حضور QR: {$group->title}", $old);

        return redirect()->route('admin.qr.groups.index')->with('success', 'تم تحديث المجموعة.');
    }

    public function destroy(QrAttendanceGroup $group): RedirectResponse
    {
        $this->guard($group);
        ActivityLog::logDelete($group, "حذف مجموعة حضور QR: {$group->title}");
        $group->delete();

        return back()->with('success', 'تم حذف المجموعة.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'title'          => ['required', 'string', 'max:150'],
            'title_en'       => ['nullable', 'string', 'max:150'],
            'default_status' => ['required', 'in:present,late,absent,excused'],
            'present_start'  => ['nullable', 'date_format:H:i'],
            'late_start'     => ['nullable', 'date_format:H:i'],
            'absent_start'   => ['nullable', 'date_format:H:i'],
            'excuse_start'   => ['nullable', 'date_format:H:i'],
            'work_days'      => ['nullable', 'array'],
            'work_days.*'    => ['integer', 'min:0', 'max:6'],
            'description'    => ['nullable', 'string', 'max:1000'],
            'is_active'      => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['work_days'] = array_values(array_map('intval', $request->input('work_days', [])));

        return $data;
    }

    private function guard(QrAttendanceGroup $group): void
    {
        $schoolId = $this->scopedSchoolId();
        abort_if($schoolId !== null && (int) $group->school_id !== $schoolId, 403, 'خارج نطاق صلاحيتك.');
    }
}
