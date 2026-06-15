<?php

namespace App\Modules\Qr\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ClassRoom;
use App\Models\User;
use App\Modules\Qr\Models\QrDayClosure;
use App\Modules\Qr\Models\QrScan;
use App\Modules\Qr\Services\QrScanService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * #265 — QR scanner page + scan-record endpoint + scan log + day-close.
 */
class QrScannerController extends Controller
{
    use HasSchoolScope;

    public function __construct(private readonly QrScanService $service) {}

    /** Scanner page (camera + manual entry). */
    public function scanner(): View
    {
        $schoolId = $this->scopedSchoolId();

        $recent = QrScan::with('student')
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
            ->orderByDesc('scanned_at')->limit(10)->get();

        return view('admin.qr.scanner', compact('recent'));
    }

    /** Scan-record endpoint (camera-decoded token or manual code). */
    public function scan(Request $request): JsonResponse
    {
        $schoolId = $this->scopedSchoolId();
        $data = $request->validate([
            'token'       => ['required', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:120'],
            'scan_time'   => ['nullable', 'date'],
            'channel'     => ['nullable', 'in:camera,manual,iot'],
        ]);

        $result = $this->service->record($data['token'], $schoolId, [
            'channel'     => $data['channel'] ?? 'manual',
            'device_name' => $data['device_name'] ?? null,
            'scan_time'   => $data['scan_time'] ?? null,
            'recorded_by' => $request->user()->id,
        ]);

        if ($result['ok']) {
            ActivityLog::log('qr.scan', 'تسجيل حضور عبر ماسح QR — '.optional($result['student'])->name);
        }

        return response()->json([
            'success'     => $result['ok'],
            'status'      => $result['status'],
            'message'     => $result['message'],
            'error_code'  => $result['error_code'],
            'student'     => $result['student'] ? ['id' => $result['student']->id, 'name' => $result['student']->name] : null,
        ], $result['ok'] ? 200 : 422);
    }

    /** Scan log page. */
    public function log(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();

        $classes = ClassRoom::with('section')
            ->when($schoolId !== null, fn ($q) => $q->whereHas('section', fn ($s) => $s->where('school_id', $schoolId)))
            ->orderBy('name')->get();

        $scans = QrScan::with(['student.classRoom'])
            ->when($schoolId !== null, fn (Builder $q) => $q->where('school_id', $schoolId))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('scan_date', $request->date))
            ->when($request->filled('status'), fn ($q) => $q->where('result_status', $request->status))
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->channel))
            ->when($request->filled('name'), fn ($q) => $q->whereHas('student', fn ($s) => $s->where('name', 'like', '%'.$request->name.'%')))
            ->orderByDesc('scanned_at')->paginate(30)->withQueryString();

        return view('admin.qr.log', compact('scans', 'classes'));
    }

    /** Execute day-close: lock the date + mark non-scanned students absent. */
    public function closeDay(Request $request): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $data = $request->validate([
            'close_date' => ['required', 'date'],
            'class_id'   => ['nullable', 'integer', 'exists:classes,id'],
            'confirm'    => ['accepted'],
        ]);

        // resolve school for the closure record
        $closureSchool = $schoolId;
        if ($closureSchool === null && $data['class_id']) {
            $closureSchool = optional(ClassRoom::with('section')->find($data['class_id'])->section)->school_id;
        }

        $closure = QrDayClosure::firstOrCreate(
            [
                'school_id'  => $closureSchool,
                'class_id'   => $data['class_id'] ?? null,
                'close_date' => $data['close_date'],
            ],
            ['closed_by' => $request->user()->id]
        );

        // mark non-scanned students of the class absent (canonical attendances)
        $marked = 0;
        if ($data['class_id']) {
            $class = ClassRoom::with(['students', 'section'])->find($data['class_id']);
            // Class must be inside the caller's school (super-admin null scope = any).
            abort_unless($class && ($schoolId === null || (int) optional($class->section)->school_id === $schoolId), 403, 'هذا الفصل خارج نطاق صلاحيتك.');
            $yearId = \App\Models\AcademicYear::where('is_current', true)->value('id');
            foreach ($class->students as $student) {
                $scanned = QrScan::where('student_id', $student->id)
                    ->whereDate('scan_date', $data['close_date'])
                    ->where('result_status', '!=', 'rejected')->exists();
                if (! $scanned) {
                    \App\Models\Attendance::updateOrCreate(
                        ['student_id' => $student->id, 'class_id' => $class->id, 'date' => $data['close_date'], 'period' => null],
                        ['teacher_id' => $request->user()->id, 'academic_year_id' => $yearId, 'status' => 'absent']
                    );
                    $marked++;
                }
            }
        }

        ActivityLog::logCreate($closure, "إغلاق يوم QR ({$data['close_date']}) — تسجيل {$marked} غائب");

        return back()->with('success', "تم إغلاق اليوم. تم تسجيل {$marked} طالب كغائب.");
    }
}
