<?php

namespace App\Modules\SmsServices\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Modules\SmsServices\Models\SmsSender;
use App\Modules\SmsServices\Models\SmsSenderAttachment;
use App\Modules\SmsServices\Repositories\Contracts\SmsSettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SmsSenderRequestController extends Controller
{
    public function __construct(private SmsSettingsRepository $repo) {}

    public function index(School $school): View
    {
        $senders = $this->repo->listSendersForSchool($school);
        return view('admin.sms-services.senders-index', compact('school', 'senders'));
    }

    public function create(School $school): View
    {
        return view('admin.sms-services.senders-create', compact('school'));
    }

    public function store(Request $request, School $school): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => [
                'required', 'string', 'max:11',
                // Arabic letters + space only (sender names are tight per KSA carrier rules).
                'regex:/^[\\x{0600}-\\x{06FF}\\s]+$/u',
            ],
            'name_en' => [
                'required', 'string', 'max:11',
                'regex:/^[A-Za-z0-9 \\-]+$/',
            ],
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:5120',
            'providers' => 'nullable|array',
            'providers.*' => Rule::in(['stc', 'mobily', 'zain']),
        ], [
            'name_ar.regex' => __('sms_services.name_ar_regex'),
            'name_en.regex' => __('sms_services.name_en_regex'),
        ]);

        $sender = $this->repo->createSender($school, [
            'name_ar' => $data['name_ar'],
            'name_en' => $data['name_en'],
        ]);

        if (!empty($data['attachments']) && !empty($data['providers'])) {
            foreach ($data['attachments'] as $idx => $file) {
                $provider = $data['providers'][$idx] ?? null;
                if (!$provider) continue;
                $path = $file->store('sms-sender-attachments', 'public');
                SmsSenderAttachment::create([
                    'sender_id' => $sender->id,
                    'provider' => $provider,
                    'file_path' => $path,
                ]);
            }
        }

        return redirect()
            ->route('admin.sms-services.senders.index', $school)
            ->with('success', __('sms_services.sender_request_submitted'));
    }

    public function destroy(School $school, SmsSender $sender): RedirectResponse
    {
        abort_if($sender->school_id !== $school->id, 404);
        $this->repo->deleteSender($sender);
        return back()->with('success', __('common.deleted_successfully'));
    }

    /**
     * Generate a blank carrier authorization template (STC / Mobily / Zain).
     *
     * Built on the fly with dompdf so no binary assets need committing; the
     * school prints it, signs/stamps it, and re-uploads it as an attachment.
     */
    public function downloadTemplate(string $provider)
    {
        $labels = ['stc' => 'STC', 'mobily' => 'Mobily', 'zain' => 'Zain'];
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.sms-services.template', [
            'provider' => $provider,
            'providerLabel' => $labels[$provider] ?? strtoupper($provider),
        ]);

        return $pdf->download("sms-authorization-{$provider}.pdf");
    }
}
