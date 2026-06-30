<?php

namespace App\Modules\SmsServices\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\User;
use App\Modules\SmsServices\Actions\SendSmsBatchAction;
use App\Modules\SmsServices\Models\SchoolSmsSetting;
use App\Modules\SmsServices\Models\SmsSender;
use App\Modules\SmsServices\Models\SmsTemplate;
use App\Modules\SmsServices\Services\CreditService;
use App\Modules\SmsServices\Support\SmsSegmentCalculator;
use App\Modules\SmsServices\Support\SmsTemplateRenderer;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Whatsapp\Repositories\Contracts\RecipientRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trello #239 — compose & send SMS (single/group). Excel send lives in
 * SmsExcelController. Tenant-scoped via scopedSchoolId() (fail-closed).
 */
class SmsSendController extends Controller
{
    use HasSchoolScope;

    public const AUDIENCES = [
        'all_students'   => 'كل الطلاب',
        'all_teachers'   => 'كل المعلمين',
        'all_parents'    => 'كل أولياء الأمور',
        'grade_students' => 'طلاب صف معين',
        'class_students' => 'طلاب فصل معين',
        'grade_parents'  => 'أولياء أمور صف معين',
        'class_parents'  => 'أولياء أمور فصل معين',
        'specific_users' => 'مستخدمون محددون',
    ];

    public function __construct(private readonly RecipientRepository $recipients) {}

    private function gate(): void
    {
        abort_unless(auth()->user()?->canDo('sms.send'), 403);
    }

    public function create(): View
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();

        $setting = $schoolId ? SchoolSmsSetting::where('school_id', $schoolId)->first() : null;
        $credit  = new CreditService();

        return view('admin.sms-services.send', [
            'audiences'   => self::AUDIENCES,
            'classes'     => $this->recipients->classesForSchool($schoolId),
            'gradeLevels' => $this->recipients->gradeLevelsForSchool($schoolId),
            'templates'   => $this->templatesFor($schoolId),
            'senders'     => $this->sendersFor($schoolId),
            'setting'     => $setting,
            'available'   => $setting ? $credit->available($setting) : 0,
            'variables'   => SmsTemplateRenderer::VARIABLES,
            'isAllSchools'=> $schoolId === null,
            'school'      => $schoolId ? School::find($schoolId) : null,
        ]);
    }

    /** AJAX: resolve a recipient group → list with phone validity. */
    public function resolveRecipients(Request $request): JsonResponse
    {
        $this->gate();
        $data = $request->validate([
            'audience'   => ['required', 'string'],
            'ref_id'     => ['nullable', 'integer'],
            'user_ids'   => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
        ]);

        $schoolId = $this->scopedSchoolId();

        $users = $data['audience'] === 'specific_users'
            ? $this->recipients->findUsers($data['user_ids'] ?? [], $schoolId)
            : $this->recipients->resolveAudience($data['audience'], $schoolId, $data['ref_id'] ?? null);

        $seen = [];
        $out  = [];
        foreach ($users->unique('id') as $u) {
            $norm = \App\Modules\Whatsapp\Support\PhoneNormalizer::normalize($u->phone ?? null);
            if ($norm === null) {
                $status = 'no_number';
            } elseif (! \App\Modules\Whatsapp\Support\PhoneNormalizer::isValid($norm)) {
                $status = 'invalid_number';
            } elseif (isset($seen[$norm])) {
                $status = 'duplicate';
            } else {
                $status = 'valid';
                $seen[$norm] = true;
            }
            $out[] = [
                'id'     => $u->id,
                'name'   => $u->name,
                'role'   => $u->role_name ?? '',
                'number' => $norm,
                'status' => $status,
            ];
        }

        return response()->json(['success' => true, 'data' => $out]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $this->gate();
        $term     = trim((string) $request->query('q', ''));
        $schoolId = $this->scopedSchoolId();

        $users = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['student', 'teacher', 'parent']))
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")))
            ->limit(30)
            ->get(['id', 'name', 'phone', 'school_id']);

        return response()->json([
            'success' => true,
            'data'    => $users->map(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'role' => $u->role_name ?? '',
            ])->all(),
        ]);
    }

    /** AJAX preview: render the final body for a sample + segment math. */
    public function preview(Request $request): JsonResponse
    {
        $this->gate();
        $body = (string) $request->input('body', '');
        $analysis = SmsSegmentCalculator::analyze($body);

        return response()->json(['success' => true, 'data' => $analysis]);
    }

    public function store(Request $request, SendSmsBatchAction $action): RedirectResponse
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();

        $data = $request->validate([
            'sender_id'      => ['nullable', 'integer'],
            'template_id'    => ['nullable', 'integer'],
            'body'           => ['required', 'string', 'max:1600'],
            'audience'       => ['required', 'string'],
            'ref_id'         => ['nullable', 'integer'],
            'recipient_ids'  => ['required', 'array', 'min:1'],
            'recipient_ids.*'=> ['integer'],
            'on_missing'     => ['nullable', 'in:blank,dash'],
        ]);

        // sender must be a usable sender for this school (or none)
        $sender = null;
        if (! empty($data['sender_id'])) {
            $sender = SmsSender::where('school_id', $schoolId)
                ->whereIn('status', ['accepted', 'active', 'approved'])
                ->find($data['sender_id']);
            if (! $sender) {
                return back()->withInput()->with('error', __('sms_send.sender_not_usable'));
            }
        }

        // template (optional) scoped to school
        $templateId = null;
        if (! empty($data['template_id'])) {
            $tpl = SmsTemplate::where('school_id', $schoolId)->find($data['template_id']);
            $templateId = $tpl?->id;
        }

        // resolve recipients server-side (never trust the client list blindly)
        $users = $data['audience'] === 'specific_users'
            ? $this->recipients->findUsers($data['recipient_ids'], $schoolId)
            : $this->recipients->resolveAudience($data['audience'], $schoolId, $data['ref_id'] ?? null)
                ->whereIn('id', $data['recipient_ids']);

        if ($users->isEmpty()) {
            return back()->withInput()->with('error', __('sms_send.no_recipients'));
        }

        $school = $schoolId ? School::find($schoolId) : null;
        $rows = $users->unique('id')->map(fn ($u) => [
            'phone'   => $u->phone,
            'name'    => $u->name,
            'role'    => $u->role_name ?? null,
            'user_id' => $u->id,
            'vars'    => SmsTemplateRenderer::varsForUser($u, $school),
        ])->values()->all();

        $batch = $action->execute(
            schoolId: $schoolId,
            senderUserId: auth()->id(),
            sender: $sender,
            templateId: $templateId,
            body: $data['body'],
            recipients: $rows,
            source: 'compose',
            name: 'إرسال SMS',
            onMissing: $data['on_missing'] ?? 'blank',
        );

        return redirect()
            ->route('admin.sms.send')
            ->with('success', __('sms_send.batch_done', [
                'sent'    => $batch->sent_count,
                'queued'  => $batch->queued_count,
                'failed'  => $batch->failed_count,
                'skipped' => $batch->skipped_count,
            ]));
    }

    private function templatesFor(?int $schoolId)
    {
        return SmsTemplate::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'body']);
    }

    private function sendersFor(?int $schoolId)
    {
        // Ensure the school always has at least one usable sender so the send
        // form's «اسم المرسل» dropdown is never empty (QA #238). The sandbox
        // sends via the Log driver, so a default sender suffices end-to-end;
        // real sender names added through the request/approval flow (#243)
        // simply join this list.
        if ($schoolId) {
            SmsSender::firstOrCreate(
                ['school_id' => $schoolId, 'name_en' => 'Platform'],
                ['name_ar' => 'المنصة', 'kind' => 'alerts', 'status' => 'active'],
            );
        }

        return SmsSender::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereIn('status', ['accepted', 'active', 'approved'])
            ->orderBy('name_ar')
            ->get(['id', 'name_ar', 'name_en']);
    }
}
