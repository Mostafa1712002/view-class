<?php

namespace App\Modules\SmsServices\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Modules\SmsServices\Repositories\Contracts\SmsSettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SmsServicesController extends Controller
{
    public function __construct(private SmsSettingsRepository $repo) {}

    public function index(): View
    {
        $schools = $this->repo->paginateSchoolsWithSettings(15);
        return view('admin.sms-services.index', compact('schools'));
    }

    public function editConnection(School $school): View
    {
        $setting = $this->repo->findOrCreateForSchool($school);
        return view('admin.sms-services.connection', compact('school', 'setting'));
    }

    public function updateConnection(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'provider'   => 'nullable|string|max:60',
            'api_key'    => 'nullable|string|max:255',
            'api_secret' => 'nullable|string|max:255',
            'sms_total'  => 'nullable|integer|min:0',
            'is_active'  => 'sometimes|boolean',
        ]);

        $setting = $this->repo->findOrCreateForSchool($school);
        $this->repo->saveSettings($setting, [
            'provider'   => $data['provider'] ?? $setting->provider,
            'api_key'    => $data['api_key'] ?? null,
            'api_secret' => $data['api_secret'] ?? null,
            'sms_total'  => $data['sms_total'] ?? $setting->sms_total,
            'is_active'  => (bool) ($data['is_active'] ?? false),
        ]);

        return redirect()
            ->route('admin.sms-services.index')
            ->with('success', __('common.updated_successfully'));
    }

    public function editDefaultSender(School $school): View
    {
        $setting = $this->repo->findOrCreateForSchool($school);
        $senders = $this->repo->listApprovedSendersForSchool($school);
        return view('admin.sms-services.default-sender', compact('school', 'setting', 'senders'));
    }

    public function updateDefaultSender(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'default_sender_id' => 'nullable|exists:sms_senders,id',
        ]);
        $setting = $this->repo->findOrCreateForSchool($school);
        // Guard: only allow approved senders belonging to this school.
        if (!empty($data['default_sender_id'])) {
            $approved = $this->repo->listApprovedSendersForSchool($school)
                ->pluck('id')->all();
            if (!in_array((int) $data['default_sender_id'], $approved, true)) {
                return back()->with('error', __('sms_services.sender_not_approved'));
            }
        }
        $this->repo->saveSettings($setting, [
            'default_sender_id' => $data['default_sender_id'] ?? null,
        ]);
        return redirect()
            ->route('admin.sms-services.index')
            ->with('success', __('common.updated_successfully'));
    }

    public function toggleActive(School $school): RedirectResponse
    {
        $setting = $this->repo->findOrCreateForSchool($school);
        $this->repo->toggleActive($setting);
        return redirect()
            ->route('admin.sms-services.index')
            ->with('success', __('common.updated_successfully'));
    }

    public function messages(School $school): View
    {
        $setting  = $this->repo->findOrCreateForSchool($school);
        $messages = $this->repo->paginateMessagesForSchool($school);
        return view('admin.sms-services.messages', compact('school', 'setting', 'messages'));
    }

    /**
     * Validate the saved API credentials against the configured provider.
     *
     * No real SMS gateway is wired yet, so this performs a local sanity check
     * (credentials present + service enabled) and reports the outcome. When a
     * real provider is integrated, replace the body with an actual ping/balance
     * call and keep the same flash-message contract.
     */
    public function testConnection(School $school): RedirectResponse
    {
        $setting = $this->repo->findOrCreateForSchool($school);

        if (empty($setting->api_key) || empty($setting->api_secret)) {
            return back()->with('error', __('sms_services.test_missing_credentials'));
        }

        return back()->with('success', __('sms_services.test_stub_ok', ['provider' => $setting->provider]));
    }
}
