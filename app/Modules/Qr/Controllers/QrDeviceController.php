<?php

namespace App\Modules\Qr\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Modules\Qr\Models\QrDevice;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * #265 — IoT scanner devices registry (list / register / toggle / re-key / delete).
 */
class QrDeviceController extends Controller
{
    use HasSchoolScope;

    public function index(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();

        $devices = QrDevice::query()
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->when($request->filled('name'), fn ($q) => $q->where('name', 'like', '%'.$request->name.'%'))
            ->when($request->filled('status') && $request->status !== '', fn ($q) => $q->where('is_active', (bool) $request->status))
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.qr.devices.index', compact('devices'));
    }

    public function store(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
        ]);

        $device = QrDevice::create([
            'school_id'  => $schoolId,
            'name'       => $data['name'],
            'location'   => $data['location'] ?? null,
            'device_key' => QrDevice::generateKey(),
            'is_active'  => true,
        ]);
        ActivityLog::logCreate($device, "تسجيل جهاز IoT: {$device->name}");

        return back()->with('success', 'تم تسجيل الجهاز بنجاح.');
    }

    public function toggle(QrDevice $device): RedirectResponse
    {
        $this->guard($device);
        $old = $device->only('is_active');
        $device->update(['is_active' => ! $device->is_active]);
        ActivityLog::logUpdate($device, $device->is_active ? 'تفعيل جهاز IoT' : 'تعطيل جهاز IoT', $old);

        return back()->with('success', $device->is_active ? 'تم تفعيل الجهاز.' : 'تم تعطيل الجهاز.');
    }

    public function regenerate(QrDevice $device): RedirectResponse
    {
        $this->guard($device);
        $old = $device->only('device_key');
        $device->update(['device_key' => QrDevice::generateKey()]);
        ActivityLog::logUpdate($device, 'إعادة توليد مفتاح جهاز IoT', $old);

        return back()->with('success', 'تم إعادة توليد مفتاح الجهاز.');
    }

    public function destroy(QrDevice $device): RedirectResponse
    {
        $this->guard($device);
        ActivityLog::logDelete($device, "حذف جهاز IoT: {$device->name}");
        $device->delete();

        return back()->with('success', 'تم حذف الجهاز.');
    }

    private function guard(QrDevice $device): void
    {
        $schoolId = $this->scopedSchoolId();
        abort_if($schoolId !== null && (int) $device->school_id !== $schoolId, 403, 'خارج نطاق صلاحيتك.');
    }
}
