<?php

namespace App\Modules\Whatsapp\Controllers;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\Whatsapp\Actions\ResolveRecipientsAction;
use App\Modules\Whatsapp\Actions\SendBroadcastAction;
use App\Modules\Whatsapp\DTOs\ComposeMessageDto;
use App\Modules\Whatsapp\Http\Requests\ComposeMessageRequest;
use App\Modules\Whatsapp\Models\SchoolWhatsappSetting;
use App\Modules\Whatsapp\Repositories\Contracts\RecipientRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsappSendController extends Controller
{
    use HasSchoolScope;

    /** Human labels for the audience groups (used for the broadcast record). */
    public const AUDIENCES = [
        'all_students'    => 'كل الطلاب',
        'all_teachers'    => 'كل المعلمين',
        'all_parents'     => 'كل أولياء الأمور',
        'grade_students'  => 'طلاب صف معين',
        'class_students'  => 'طلاب فصل معين',
        'grade_parents'   => 'أولياء أمور صف معين',
        'class_parents'   => 'أولياء أمور فصل معين',
        'school_teachers' => 'معلمو مدرسة معينة',
        'specific_users'  => 'مستخدمون محددون',
    ];

    public function __construct(
        private readonly RecipientRepository $recipients,
    ) {}

    private function gate(): void
    {
        abort_unless(auth()->user()?->canDo('whatsapp.send'), 403);
    }

    /**
     * Compose page.
     */
    public function create(): View
    {
        $this->gate();

        $schoolId = $this->scopedSchoolId();

        // service-enabled / credit hints (null-safe; null school = super-admin)
        $setting = $schoolId
            ? SchoolWhatsappSetting::where('school_id', $schoolId)->first()
            : null;

        $classes     = $this->recipients->classesForSchool($schoolId);
        $gradeLevels = $this->recipients->gradeLevelsForSchool($schoolId);
        $school      = $schoolId ? School::find($schoolId) : null;

        return view('admin.whatsapp.send', [
            'audiences'   => self::AUDIENCES,
            'classes'     => $classes,
            'gradeLevels' => $gradeLevels,
            'setting'     => $setting,
            'school'      => $school,
            'isAllSchools' => $schoolId === null,
        ]);
    }

    /**
     * AJAX: resolve a recipient group into a list of users with number status.
     */
    public function resolveRecipients(Request $request, ResolveRecipientsAction $action): JsonResponse
    {
        $this->gate();

        $validated = $request->validate([
            'audience'        => ['required', 'string'],
            'ref_id'          => ['nullable', 'integer'],
            'user_ids'        => ['nullable', 'array'],
            'user_ids.*'      => ['integer'],
        ]);

        $schoolId = $this->scopedSchoolId();

        $list = $action->execute(
            $validated['audience'],
            $schoolId,
            $validated['ref_id'] ?? null,
            $validated['user_ids'] ?? []
        );

        return response()->json([
            'success' => true,
            'data'    => $list,
        ]);
    }

    /**
     * AJAX: searchable user list for the "specific users" picker.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $this->gate();

        $term     = trim((string) $request->query('q', ''));
        $schoolId = $this->scopedSchoolId();

        $users = \App\Models\User::query()
            ->with('roles')
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['student', 'teacher', 'parent']))
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->when($term !== '', fn ($q) => $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                  ->orWhere('phone', 'like', "%{$term}%")
                  ->orWhere('whatsapp', 'like', "%{$term}%");
            }))
            ->limit(30)
            ->get(['id', 'name', 'phone', 'whatsapp', 'school_id']);

        return response()->json([
            'success' => true,
            'data'    => $users->map(fn ($u) => [
                'id'   => $u->id,
                'name' => $u->name,
                'role' => $u->role_name,
            ])->all(),
        ]);
    }

    /**
     * Persist + send the broadcast.
     */
    public function store(ComposeMessageRequest $request, SendBroadcastAction $action): RedirectResponse
    {
        $this->gate();

        $user     = auth()->user();
        // scopedSchoolId(): same value as activeSchoolId() for any legitimate
        // user (used here both as the broadcast's school_id write value AND to
        // scope recipient resolution inside SendBroadcastAction), but fails
        // closed if a non-super-admin somehow resolves to a null (all-schools)
        // scope — so a send can never fan out across tenants.
        $schoolId = $this->scopedSchoolId();

        // Guard: WhatsApp service must be enabled for the active school
        // (super-admin / all-schools mode bypasses — uses log driver).
        if ($schoolId !== null) {
            $setting = SchoolWhatsappSetting::where('school_id', $schoolId)->first();
            if (! $setting || ! $setting->is_enabled) {
                return back()
                    ->withInput()
                    ->with('error', 'خدمة واتساب غير مفعّلة لهذه المدرسة. فعّلها من إعدادات واتساب أولاً.');
            }
        }

        $type = $request->input('message_type');

        // Store uploaded media (if any) before building the DTO.
        $mediaPath = null;
        $mediaName = null;
        if ($type === 'image' && $request->hasFile('image')) {
            $mediaName = $request->file('image')->getClientOriginalName();
            $mediaPath = $request->file('image')->store('whatsapp/images', 'public');
        } elseif ($type === 'pdf' && $request->hasFile('pdf')) {
            $mediaName = $request->file('pdf')->getClientOriginalName();
            $mediaPath = $request->file('pdf')->store('whatsapp/pdfs', 'public');
        }

        $dto = new ComposeMessageDto(
            messageType: $type,
            body: $request->cleanBody(),
            mediaPath: $mediaPath,
            mediaOriginalName: $mediaName,
            audience: $request->input('audience'),
            audienceLabel: self::AUDIENCES[$request->input('audience')] ?? $request->input('audience'),
            recipientIds: array_map('intval', $request->input('recipient_ids', [])),
            schoolId: $schoolId,
            senderId: $user->id,
        );

        $broadcast = $action->execute($dto);

        if ($broadcast->total_recipients === 0) {
            return back()->withInput()->with('error', 'لم يتم العثور على مستلمين صالحين للإرسال.');
        }

        return redirect()
            ->route('admin.whatsapp.send')
            ->with('success', sprintf(
                'تمت معالجة الرسالة: تم الإرسال إلى %d، فشل %d، تم تخطي %d.',
                $broadcast->sent_count,
                $broadcast->failed_count,
                $broadcast->skipped_count
            ));
    }
}
