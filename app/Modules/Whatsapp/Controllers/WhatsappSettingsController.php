<?php

namespace App\Modules\Whatsapp\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Whatsapp\Http\Requests\WhatsappSettingsRequest;
use App\Modules\Whatsapp\Models\SchoolWhatsappSetting;
use App\Modules\Whatsapp\Models\WhatsappLog;
use App\Modules\Whatsapp\Repositories\Contracts\WhatsappSettingsRepository;
use App\Modules\Whatsapp\Services\WhatsappService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappSettingsController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private readonly WhatsappSettingsRepository $repo,
    ) {}

    /**
     * Super-admin: paginated list of schools with their WhatsApp settings.
     * School-admin: redirect directly to their school's edit form.
     */
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin()) {
            $school = School::findOrFail($user->school_id);
            return redirect()->route('admin.whatsapp.edit', $school);
        }

        $schools = School::with('whatsappSetting')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(20);

        return view('admin.whatsapp.settings', compact('schools'));
    }

    /**
     * Edit WhatsApp settings for a specific school.
     */
    public function edit(School $school): View
    {
        $setting = $this->repo->findOrCreateForSchool($school);

        return view('admin.whatsapp.settings', compact('school', 'setting'));
    }

    /**
     * Save WhatsApp settings for a school.
     */
    public function update(School $school, WhatsappSettingsRequest $request): RedirectResponse
    {
        $setting = $this->repo->findOrCreateForSchool($school);
        $data    = $request->validated();

        // If api_token is blank string, keep existing encrypted value
        if (empty($data['api_token'])) {
            unset($data['api_token']);
        }

        $this->repo->saveSettings($setting, $data);

        return back()->with('success', 'تم حفظ إعدادات واتساب بنجاح.');
    }

    /**
     * Paginated logs for the active school.
     */
    public function logs(Request $request): View
    {
        $schoolId = $this->activeSchoolId();
        $school   = School::findOrFail($schoolId);

        $filters = $request->only(['status', 'date_from', 'date_to', 'student_name']);
        $logs    = $this->repo->getLogsForSchool($school, $filters);

        return view('admin.whatsapp.logs', compact('school', 'logs', 'filters'));
    }

    /**
     * Resend a failed or pending WhatsApp log entry.
     */
    public function resend(WhatsappLog $log): RedirectResponse
    {
        $user = auth()->user();

        if (!$user->isSuperAdmin() && $user->school_id !== $log->school_id) {
            abort(403);
        }

        $attendance = $log->attendance;
        $parent     = $log->parent;

        if (!$attendance || !$parent) {
            return back()->with('error', 'لا يمكن إعادة الإرسال: البيانات المرتبطة غير موجودة.');
        }

        $setting = SchoolWhatsappSetting::where('school_id', $log->school_id)->first();
        $service = new WhatsappService($setting);

        // Send directly (bypassing duplicate check) by calling driver
        $driver = $service->resolveDriver();
        $result = $driver->send($log->to_number, $log->message_text);

        $log->update([
            'status'         => $result['success'] ? 'sent' : 'failed',
            'failure_reason' => $result['failure_reason'],
            'sent_at'        => $result['success'] ? now() : null,
            'triggered_by'   => $user->id,
        ]);

        $message = $result['success']
            ? 'تم إعادة الإرسال بنجاح.'
            : 'فشل إعادة الإرسال: ' . ($result['failure_reason'] ?? 'خطأ غير معروف');

        return back()->with($result['success'] ? 'success' : 'error', $message);
    }
}
