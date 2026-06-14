<?php

namespace App\Modules\Attendance\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Notification;
use App\Modules\Attendance\Repositories\Contracts\AttendanceRepository;
use App\Modules\Whatsapp\Models\SchoolWhatsappSetting;
use App\Modules\Whatsapp\Services\WhatsappService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ExcuseController extends Controller
{
    public function __construct(
        private readonly AttendanceRepository $repo,
    ) {}

    /**
     * Parent submits an excuse for an absence/late record.
     * Route: POST parent/child/{child}/attendance/{attendance}/excuse
     */
    public function store(Request $request, int $child, Attendance $attendance): RedirectResponse
    {
        $request->validate([
            'excuse_text' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'excuse_text.required' => 'يرجى كتابة نص العذر.',
            'excuse_text.min'      => 'يجب أن يكون نص العذر على الأقل 5 أحرف.',
        ]);

        $parent = auth()->user();

        // Verify this parent is linked to the student
        $isLinked = $parent->children()
            ->where('users.id', $attendance->student_id)
            ->exists();

        if (!$isLinked) {
            abort(403, 'غير مصرح لك بتقديم عذر لهذا الطالب.');
        }

        // Only absences and late records can have excuses
        if (!in_array($attendance->status, ['absent', 'late'], true)) {
            return back()->with('error', 'لا يمكن تقديم عذر لهذا السجل.');
        }

        // Only allow one excuse submission
        if ($attendance->excuse_status !== null) {
            return back()->with('error', 'تم تقديم العذر مسبقاً.');
        }

        $this->repo->submitExcuse($attendance, $request->excuse_text, $parent);

        return back()->with('success', 'تم تقديم العذر بنجاح وسيتم مراجعته من قِبل الإدارة.');
    }

    /**
     * Admin reviews (accepts/rejects) a parent's excuse.
     * Route: POST admin/attendance/{attendance}/excuse/review
     */
    public function review(Request $request, Attendance $attendance): RedirectResponse
    {
        $request->validate([
            'decision' => ['required', 'in:accepted,rejected'],
        ]);

        $reviewer = auth()->user();

        // Multi-tenant guard: a non-super-admin may only review excuses for
        // students in their own school.
        $student = $attendance->student;
        abort_if(
            ! $reviewer || (! $reviewer->isSuperAdmin() && (int) ($student?->school_id) !== (int) $reviewer->school_id),
            403,
            'غير مصرح لك بمراجعة هذا العذر.'
        );

        if ($attendance->excuse_status !== 'pending') {
            return back()->with('error', 'هذا العذر تمت مراجعته مسبقاً أو لم يُقدَّم بعد.');
        }

        $decision = $request->decision;

        $this->repo->reviewExcuse($attendance, $decision, $reviewer);

        // Notify parents
        $student = $attendance->student;
        if ($student) {
            $parents = $student->parents()
                ->wherePivot('can_receive_notifications', true)
                ->get();

            $label = $decision === 'accepted' ? 'مقبول' : 'مرفوض';
            $dateFormatted = $attendance->date?->format('Y-m-d') ?? '';

            $setting = SchoolWhatsappSetting::where('school_id', $student->school_id)->first();
            $whatsappService = new WhatsappService($setting);

            foreach ($parents as $parent) {
                // In-app notification
                try {
                    Notification::create([
                        'user_id'     => $parent->id,
                        'type'        => 'attendance_alert',
                        'title'       => 'تحديث على العذر المقدم',
                        'body'        => "تم {$label} عذر الطالب/ة {$student->name} للغياب بتاريخ {$dateFormatted}.",
                        'icon'        => $decision === 'accepted' ? 'bi-check-circle' : 'bi-x-circle',
                        'color'       => $decision === 'accepted' ? 'success' : 'danger',
                        'action_url'  => route('parent.child.attendance', $student),
                        'action_text' => 'عرض سجل الحضور',
                        'data'        => [
                            'attendance_id' => $attendance->id,
                            'excuse_status' => $decision,
                        ],
                    ]);
                } catch (Throwable $e) {
                    Log::error('[ExcuseController] In-app notify failed', ['error' => $e->getMessage()]);
                }

                // WhatsApp notification
                try {
                    $type = $decision === 'accepted' ? 'excuse_accepted' : 'excuse_rejected';
                    $whatsappService->sendAbsenceAlert($attendance, $parent, $type);
                } catch (Throwable $e) {
                    Log::error('[ExcuseController] WhatsApp notify failed', ['error' => $e->getMessage()]);
                }
            }
        }

        $message = $decision === 'accepted' ? 'تم قبول العذر بنجاح.' : 'تم رفض العذر.';

        return back()->with('success', $message);
    }
}
