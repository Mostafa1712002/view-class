<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Notification;
use App\Models\User;
use App\Modules\Certificates\Actions\IssueCertificatesAction;
use App\Modules\Certificates\Actions\RenderCertificatePdfAction;
use App\Modules\Certificates\Http\Requests\IssueCertificateRequest;
use App\Modules\Certificates\Http\Requests\StoreCertificateRequest;
use App\Modules\Certificates\Http\Requests\UpdateCertificateRequest;
use App\Modules\Certificates\Repositories\Contracts\CertificateRepository;
use App\Modules\Certificates\Repositories\Contracts\CertificateTemplateRepository;
use App\Modules\SmsServices\Models\SchoolSmsSetting;
use App\Modules\SmsServices\Services\SmsService;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Whatsapp\Drivers\LogDriver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminCertificateController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private CertificateRepository $certificates,
        private CertificateTemplateRepository $templates,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'type' => $request->get('type'),
            'q'    => $request->get('q'),
        ];

        $certificates = $this->certificates->listForSchool($this->activeSchoolId(), $filters);

        return view('certificates.admin.index', compact('certificates', 'filters'));
    }

    public function create(): View
    {
        $schoolId = $this->activeSchoolId();
        $recipients = $this->getSchoolUsers($schoolId);

        return view('certificates.admin.form', [
            'certificate' => null,
            'recipients'  => $recipients,
        ]);
    }

    /**
     * Template-based issuing screen (single + bulk to students).
     */
    public function issueForm(): View
    {
        $schoolId = $this->activeSchoolId();

        return view('certificates.admin.issue', [
            'templates' => $this->templates->allForSchool($schoolId),
            'students'  => $this->getSchoolStudents($schoolId),
        ]);
    }

    /**
     * Generate + store a PDF per recipient (single or bulk), synchronously.
     */
    public function issue(IssueCertificateRequest $request, IssueCertificatesAction $action): RedirectResponse
    {
        $schoolId = $this->scopedSchoolId();
        $data = $request->validated();

        $attrs = [
            'school_id'   => $schoolId,
            'template_id' => (int) $data['template_id'],
            'type'        => $data['type'],
            'title'       => $data['title'],
            'issue_date'  => $data['issue_date'],
            'note'        => $data['note'] ?? null,
            'issued_by'   => auth()->id(),
            'status'      => 'published',
            'signer_name'    => $data['signer_name'] ?? null,
            'signature_type' => $data['signature_type'] ?? null,
            // body_html only carries meaning for the free-text 'general' type.
            'body_html'      => $data['type'] === 'general' ? ($data['body_html'] ?? null) : null,
        ];

        if ($request->hasFile('logo')) {
            $attrs['logo_path'] = $request->file('logo')->store('certificates/logos', 'public');
        }

        if ($request->hasFile('stamp')) {
            $attrs['stamp_path'] = $request->file('stamp')->store('certificates/stamps', 'public');
        }

        // Signature: an uploaded file, or a canvas-drawn PNG data URL.
        if (($data['signature_type'] ?? null) === 'file' && $request->hasFile('signature_file')) {
            $attrs['signature_path'] = $request->file('signature_file')->store('certificates/signatures', 'public');
        } elseif (($data['signature_type'] ?? null) === 'manual' && ! empty($data['signature_data'])) {
            if (preg_match('/^data:image\/png;base64,(.+)$/', $data['signature_data'], $m)) {
                $decoded = base64_decode($m[1], true);
                if ($decoded !== false) {
                    $signaturePath = 'certificates/signatures/' . uniqid('sig_', true) . '.png';
                    Storage::disk('public')->put($signaturePath, $decoded);
                    $attrs['signature_path'] = $signaturePath;
                }
            }
        }

        $created = $action->execute($attrs, $data['recipient_ids']);

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', __('certificates.flash.issued', ['count' => count($created)]));
    }

    /**
     * Preview screen: recipient name, share link, view / send.
     */
    public function preview(int $certificate): View
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        return view('certificates.admin.preview', ['certificate' => $cert]);
    }

    /**
     * Stream the generated PDF inline (regenerates from the template if missing).
     */
    public function pdf(int $certificate, RenderCertificatePdfAction $action): Response
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        $binary = $cert->file_path && Storage::disk('public')->exists($cert->file_path)
            && str_ends_with($cert->file_path, '.pdf')
            ? Storage::disk('public')->get($cert->file_path)
            : $action->execute($cert);

        return response($binary, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="certificate-' . $cert->id . '.pdf"',
        ]);
    }

    /**
     * Send screen (channels: SMS / in-platform / email / WhatsApp). The channels
     * themselves reuse existing messaging modules and are out of scope here.
     */
    public function sendForm(int $certificate): View
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        return view('certificates.admin.send', ['certificate' => $cert]);
    }

    public function store(StoreCertificateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $schoolId = $this->activeSchoolId();

        // A non super-admin must have a concrete school scope (no null-scope tenant bypass).
        abort_if(! auth()->user()->isSuperAdmin() && $schoolId === null, 403);

        $data['school_id'] = $schoolId;
        // The issuer is always the acting admin — never client-supplied.
        $data['issued_by'] = auth()->id();

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('certificates', 'public');
        }
        unset($data['file']);

        $this->certificates->create($data);

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', __('certificates.flash.created'));
    }

    public function edit(int $certificate): View
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        $schoolId = $this->activeSchoolId();
        $recipients = $this->getSchoolUsers($schoolId);

        return view('certificates.admin.form', [
            'certificate' => $cert,
            'recipients'  => $recipients,
        ]);
    }

    public function update(UpdateCertificateRequest $request, int $certificate): RedirectResponse
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        $data = $request->validated();

        if ($request->hasFile('file')) {
            // Delete old file if it exists
            if ($cert->file_path) {
                Storage::disk('public')->delete($cert->file_path);
            }
            $data['file_path'] = $request->file('file')->store('certificates', 'public');
        }
        unset($data['file']);

        $this->certificates->update($cert, $data);

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', __('certificates.flash.updated'));
    }

    public function publish(Request $request, int $certificate): RedirectResponse
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        $this->certificates->publish($cert);

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', __('certificates.flash.published'));
    }

    public function destroy(int $certificate): RedirectResponse
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        // Delete the file from disk if it exists
        if ($cert->file_path) {
            Storage::disk('public')->delete($cert->file_path);
        }

        $this->certificates->delete($cert);

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', __('certificates.flash.deleted'));
    }

    /**
     * Guard a single certificate against cross-tenant access. Super-admins are
     * global; everyone else must have a concrete active school that matches the
     * certificate's school_id. A null active school for a non super-admin is a
     * hard 403 (no null-scope bypass), never a silent match.
     */
    private function authorizeCert(Certificate $cert): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return;
        }

        $schoolId = $this->activeSchoolId();
        abort_if($schoolId === null, 403);
        abort_unless($cert->school_id === $schoolId, 404);
    }

    /**
     * Get active users for the school to populate the recipients dropdown.
     */
    private function getSchoolUsers(?int $schoolId): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->limit(2000)
            ->get(['id', 'name']);
    }

    /**
     * Students of the active school (for the issue screen recipient picker).
     */
    private function getSchoolStudents(?int $schoolId): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'student'))
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->limit(3000)
            ->get(['id', 'name']);
    }

    /**
     * Deliver the certificate share message across the selected channels. Each
     * channel is isolated in its own try/catch so one failing transport (e.g. a
     * misconfigured mailer) never 500s the whole request; a per-channel
     * sent-count is collected for the success flash.
     */
    public function sendStore(Request $request, int $certificate): RedirectResponse
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        $validated = $request->validate([
            'channels'   => ['required', 'array'],
            'channels.*' => ['in:in_platform,email,sms,whatsapp'],
            'message'    => ['required', 'string'],
        ]);

        $channels = $validated['channels'];
        $message  = $validated['message'];
        $shareUrl = $cert->share_token ? route('certificates.share', $cert->share_token) : '';

        // Recipients = the student's parents; fall back to the recipient itself.
        $student = $cert->recipient;
        $recipients = collect();
        if ($student) {
            $recipients = $student->parents()->get();
            if ($recipients->isEmpty()) {
                $recipients = collect([$student]);
            }
        }

        $deliver = [
            'in_platform' => fn () => $this->deliverInPlatform($recipients, $message, $shareUrl),
            'email'       => fn () => $this->deliverEmail($recipients, $message),
            'sms'         => fn () => $this->deliverSms($cert->school_id, $recipients, $message),
            'whatsapp'    => fn () => $this->deliverWhatsapp($recipients, $message),
        ];

        $summary = [];
        foreach ($deliver as $channel => $fn) {
            if (! in_array($channel, $channels, true)) {
                continue;
            }
            try {
                $summary[$channel] = $fn();
            } catch (\Throwable $e) {
                report($e);
                $summary[$channel] = 0;
            }
        }

        $parts = [];
        foreach ($summary as $channel => $count) {
            $parts[] = __('certificates.send_page.' . $channel) . ' (' . $count . ')';
        }

        return redirect()
            ->route('admin.certificates.preview', $cert->id)
            ->with('success', __('certificates.send_page.sent_summary', [
                'channels' => implode('، ', $parts),
            ]));
    }

    /**
     * Current render/delivery progress for a certificate (JSON, polled by a
     * refresh button on the index/preview).
     */
    public function progress(int $certificate): JsonResponse
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        return response()->json(['progress' => (int) $cert->progress]);
    }

    /**
     * In-app notification per recipient (reuses the existing App\Models\Notification
     * store used across the app — grades, attendance, announcements).
     */
    private function deliverInPlatform(Collection $recipients, string $message, string $shareUrl): int
    {
        $count = 0;
        foreach ($recipients as $user) {
            Notification::create([
                'user_id'     => $user->id,
                'type'        => 'system',
                'title'       => __('certificates.send_page.title'),
                'body'        => $message,
                'color'       => 'info',
                'action_url'  => $shareUrl ?: null,
                'action_text' => $shareUrl ? __('certificates.preview_page.view') : null,
            ]);
            $count++;
        }

        return $count;
    }

    private function deliverEmail(Collection $recipients, string $message): int
    {
        $count = 0;
        foreach ($recipients as $user) {
            if (empty($user->email)) {
                continue;
            }
            Mail::raw($message, function ($mail) use ($user) {
                $mail->to($user->email)->subject(__('certificates.send_page.title'));
            });
            $count++;
        }

        return $count;
    }

    private function deliverSms(?int $schoolId, Collection $recipients, string $message): int
    {
        if (! $schoolId) {
            return 0;
        }

        $setting = SchoolSmsSetting::firstOrCreate(
            ['school_id' => $schoolId],
            ['is_active' => false, 'sms_used' => 0, 'sms_total' => 0, 'provider' => 'generic']
        );
        $driver = (new SmsService($setting))->resolveDriver();

        $count = 0;
        foreach ($recipients as $user) {
            $phone = $user->phone ?? null;
            if (empty($phone)) {
                continue;
            }
            $result = $driver->send($phone, $message);
            if (in_array($result['status'] ?? '', ['sent', 'delivered'], true)) {
                $count++;
            }
        }

        return $count;
    }

    private function deliverWhatsapp(Collection $recipients, string $message): int
    {
        // ponytail: sandbox LogDriver (logs to daily channel) — swap for a wired
        // WhatsappService when a real provider send API for ad-hoc messages exists.
        $driver = new LogDriver();

        $count = 0;
        foreach ($recipients as $user) {
            $phone = $user->whatsapp ?? $user->phone ?? null;
            if (empty($phone)) {
                continue;
            }
            $result = $driver->send($phone, $message);
            if (! empty($result['success'])) {
                $count++;
            }
        }

        return $count;
    }
}
