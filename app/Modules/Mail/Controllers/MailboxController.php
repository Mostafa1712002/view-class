<?php

namespace App\Modules\Mail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\InternalMail;
use App\Models\InternalMailRecipient;
use App\Models\User;
use App\Modules\Mail\Http\Requests\StoreMailRequest;
use App\Modules\Mail\Repositories\Contracts\MailboxRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MailboxController extends Controller
{
    use HasSchoolScope;

    private const ALLOWED_FOLDERS = ['inbox', 'sent', 'drafts', 'starred', 'important', 'task', 'archive', 'trash'];

    public function __construct(private MailboxRepository $mailbox) {}

    public function index(Request $request, string $folder = 'inbox'): View
    {
        $this->gate('mailbox.view');

        abort_unless(in_array($folder, self::ALLOWED_FOLDERS, true), 404);

        $userId = auth()->id();

        $filters = array_filter([
            'importance' => $request->get('importance'),
            'search'     => $request->get('search') ? trim((string) $request->get('search')) : null,
            'unread'     => $request->boolean('unread') ? true : null,
        ], fn ($v) => $v !== null);

        $messages = $this->mailbox->getFolder($userId, $folder, $filters);
        $counts   = $this->mailbox->getFolderCounts($userId);

        return view('mailbox.index', compact('folder', 'messages', 'counts', 'filters'));
    }

    public function create(): View
    {
        $this->gate('mailbox.send');

        return view('mailbox.compose', $this->composeData());
    }

    public function store(StoreMailRequest $request): RedirectResponse
    {
        $isDraft = $request->input('action') === 'draft';

        // Gate: drafting needs mailbox.draft, sending needs mailbox.send.
        $this->gate($isDraft ? 'mailbox.draft' : 'mailbox.send');

        $user = auth()->user();

        $mail = InternalMail::create([
            'school_id'          => $this->activeSchoolId(),
            'sender_id'          => $user->id,
            'subject'            => $request->validated('subject'),
            'importance'         => $request->validated('importance'),
            'body'               => $request->validated('body'),
            'related_student_id' => $request->validated('related_student_id'),
            'is_draft'           => $isDraft,
        ]);

        // Handle file attachment — store on the PRIVATE disk; it is served only
        // through the authorized download() action, never a public URL.
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store("mails/{$mail->id}", 'local');
            $mail->update(['attachment_path' => $path]);
        }

        $this->syncRecipients($mail, (array) $request->validated('to'), $isDraft);

        if ($isDraft) {
            return redirect()
                ->route('my.mailbox.folder', 'drafts')
                ->with('success', __('mailbox.draft_saved'));
        }

        ActivityLog::log('mailbox.send', 'إرسال رسالة بريد داخلي: ' . Str::limit($mail->subject, 60), $mail);

        return redirect()
            ->route('my.mailbox.folder', 'sent')
            ->with('success', __('mailbox.sent_success'));
    }

    /**
     * Edit a draft. Only the sender of an unsent draft may open it for editing.
     */
    public function edit(int $mail): View
    {
        $this->gate('mailbox.draft');

        $message = $this->loadOwnDraft($mail);

        $selectedRecipients = $message->recipients->pluck('recipient_id')->all();

        return view('mailbox.compose', $this->composeData($message, $selectedRecipients));
    }

    /**
     * Persist edits to an existing draft, optionally sending it.
     */
    public function update(StoreMailRequest $request, int $mail): RedirectResponse
    {
        $isDraft = $request->input('action') === 'draft';

        $this->gate($isDraft ? 'mailbox.draft' : 'mailbox.send');

        $message = $this->loadOwnDraft($mail);

        $message->update([
            'subject'            => $request->validated('subject'),
            'importance'         => $request->validated('importance'),
            'body'               => $request->validated('body'),
            'related_student_id' => $request->validated('related_student_id'),
            'is_draft'           => $isDraft,
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store("mails/{$message->id}", 'local');
            $message->update(['attachment_path' => $path]);
        }

        // Recipient rows are rebuilt from the submitted list.
        $message->recipients()->delete();
        $this->syncRecipients($message, (array) $request->validated('to'), $isDraft);

        if ($isDraft) {
            return redirect()
                ->route('my.mailbox.folder', 'drafts')
                ->with('success', __('mailbox.draft_saved'));
        }

        ActivityLog::log('mailbox.send', 'إرسال مسودة بريد داخلي: ' . Str::limit($message->subject, 60), $message);

        return redirect()
            ->route('my.mailbox.folder', 'sent')
            ->with('success', __('mailbox.sent_success'));
    }

    /**
     * Open the compose form pre-filled to reply to a message (recipient = original sender).
     */
    public function reply(int $mail): View
    {
        $this->gate('mailbox.send');

        [$message] = $this->authorizeMail($mail);

        $prefill = [
            'subject'    => $this->prefixSubject($message->subject, __('mailbox.reply_prefix')),
            'importance' => $message->importance,
            'body'       => $this->quoteOriginal($message),
            'to'         => [$message->sender_id],
        ];

        return view('mailbox.compose', $this->composeData(null, [$message->sender_id], $prefill));
    }

    /**
     * Open the compose form pre-filled to forward a message (no preset recipient).
     */
    public function forward(int $mail): View
    {
        $this->gate('mailbox.send');

        [$message] = $this->authorizeMail($mail);

        $prefill = [
            'subject'    => $this->prefixSubject($message->subject, __('mailbox.forward_prefix')),
            'importance' => $message->importance,
            'body'       => $this->quoteOriginal($message),
            'to'         => [],
        ];

        return view('mailbox.compose', $this->composeData(null, [], $prefill));
    }

    public function show(int $mail): View|RedirectResponse
    {
        $this->gate('mailbox.view');

        [$message, $isSender, $recipientRow] = $this->authorizeMail($mail);

        // A sender opening their own unsent draft is sent to the editor instead.
        // Guard on Route::has so that, until the module routes are required in
        // routes/web.php, the draft simply opens read-only here (no 500).
        if ($isSender && $message->is_draft && \Illuminate\Support\Facades\Route::has('my.mailbox.edit')) {
            return redirect()->route('my.mailbox.edit', $message->id);
        }

        // Mark as read for the recipient
        if ($recipientRow) {
            $this->mailbox->markRead($mail, auth()->id());
            $recipientRow->refresh();
        }

        return view('mailbox.show', compact('message', 'isSender', 'recipientRow'));
    }

    /**
     * Stream a mail's attachment from the private disk, only to its sender or a
     * recipient (same authorization as show()). Never exposed via a public URL.
     */
    public function download(int $mail): \Symfony\Component\HttpFoundation\Response
    {
        $this->gate('mailbox.view');

        [$message] = $this->authorizeMail($mail);

        abort_unless($message->attachment_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($message->attachment_path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $message->attachment_path,
            basename($message->attachment_path),
            ['Content-Disposition' => 'attachment']
        );
    }

    public function star(int $mail): RedirectResponse
    {
        $row = $this->findRecipientRow($mail);
        $row->update(['starred' => true]);

        return back()->with('success', __('mailbox.starred_action'));
    }

    public function unstar(int $mail): RedirectResponse
    {
        $row = $this->findRecipientRow($mail);
        $row->update(['starred' => false]);

        return back()->with('success', __('mailbox.unstarred_action'));
    }

    public function archive(int $mail): RedirectResponse
    {
        $this->gate('mailbox.archive');

        $row = $this->findRecipientRow($mail);
        $row->update(['archived' => true]);

        return back()->with('success', __('mailbox.archived_action'));
    }

    public function unarchive(int $mail): RedirectResponse
    {
        $this->gate('mailbox.archive');

        $row = $this->findRecipientRow($mail);
        $row->update(['archived' => false]);

        return back()->with('success', __('mailbox.unarchived_action'));
    }

    public function trash(int $mail): RedirectResponse
    {
        $row = $this->findRecipientRow($mail);
        $row->update(['trashed' => true]);

        return redirect()
            ->route('my.mailbox.index')
            ->with('success', __('mailbox.trashed_action'));
    }

    public function restore(int $mail): RedirectResponse
    {
        $row = $this->findRecipientRow($mail);
        $row->update(['trashed' => false]);

        return redirect()
            ->route('my.mailbox.folder', 'trash')
            ->with('success', __('mailbox.restored_action'));
    }

    public function toggleTask(int $mail): RedirectResponse
    {
        $row = $this->findRecipientRow($mail);
        $row->update(['is_task' => ! $row->is_task]);

        return back()->with('success', __('mailbox.task_toggled'));
    }

    public function destroy(int $mail): RedirectResponse
    {
        // Permanent delete is gated — only users with mailbox.delete may purge.
        $this->gate('mailbox.delete');

        $userId     = auth()->id();
        $mailRecord = InternalMail::find($mail);

        // Discarding an unsent draft: the sender purges the whole record + its
        // (empty) recipient rows in one step.
        if ($mailRecord && $mailRecord->is_draft && $mailRecord->sender_id === $userId) {
            ActivityLog::logDelete($mailRecord, 'حذف مسودة بريد داخلي: ' . Str::limit($mailRecord->subject, 60));
            $mailRecord->recipients()->delete();
            $mailRecord->delete();

            return redirect()
                ->route('my.mailbox.folder', 'drafts')
                ->with('success', __('mailbox.deleted'));
        }

        $row = InternalMailRecipient::where('mail_id', $mail)
            ->where('recipient_id', $userId)
            ->first();

        if ($row) {
            $row->delete();
        }

        // If no recipients remain and the sender is deleting, clean up the mail record
        if ($mailRecord && $mailRecord->sender_id === $userId) {
            $remaining = InternalMailRecipient::where('mail_id', $mail)->count();
            if ($remaining === 0) {
                ActivityLog::logDelete($mailRecord, 'حذف رسالة بريد داخلي: ' . Str::limit($mailRecord->subject, 60));
                $mailRecord->delete();
            }
        }

        return redirect()
            ->route('my.mailbox.folder', 'trash')
            ->with('success', __('mailbox.deleted'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Backend permission gate. Fails closed with 403 — does not rely on route
     * middleware (the main web.php route group is not edited by this module).
     */
    private function gate(string $permission): void
    {
        abort_unless(auth()->check() && auth()->user()->canDo($permission), 403);
    }

    /**
     * Build the data the compose view needs: the scoped recipient list grouped
     * by role, the parent's children, and any prefilled values.
     *
     * @param  array<int>  $selectedRecipients
     * @param  array<string,mixed>  $prefill
     */
    private function composeData(?InternalMail $draft = null, array $selectedRecipients = [], array $prefill = []): array
    {
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();

        // School-scoped, role-narrowed candidate recipients. A non-super-admin
        // can only address users inside their own school.
        $candidates = User::query()
            ->with('roles:id,slug,name')
            ->when($schoolId && ! $user->isSuperAdmin(), fn ($q) => $q->where('school_id', $schoolId))
            ->whereNull('deleted_at')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->limit(2000)
            ->get(['id', 'name', 'school_id']);

        // Group candidates by primary role so the compose form can offer the
        // card's "اختيار مجموعة" buckets (students / teachers / parents / admins).
        $roleGroups = [
            'student'      => ['label' => __('mailbox.group_students'), 'ids' => []],
            'teacher'      => ['label' => __('mailbox.group_teachers'), 'ids' => []],
            'parent'       => ['label' => __('mailbox.group_parents'),  'ids' => []],
            'school-admin' => ['label' => __('mailbox.group_admins'),   'ids' => []],
        ];

        foreach ($candidates as $candidate) {
            foreach ($candidate->roles as $role) {
                if (isset($roleGroups[$role->slug])) {
                    $roleGroups[$role->slug]['ids'][] = $candidate->id;
                }
            }
        }

        // Drop empty buckets so the UI only shows groups that have members.
        $roleGroups = array_filter($roleGroups, fn ($g) => ! empty($g['ids']));

        $children = $user->isParent()
            ? $user->children()->get(['users.id', 'users.name'])
            : collect();

        return [
            'recipients'         => $candidates,
            'roleGroups'         => $roleGroups,
            'children'           => $children,
            'draft'              => $draft,
            'selectedRecipients' => $selectedRecipients,
            'prefill'            => $prefill,
        ];
    }

    /**
     * Create recipient rows + a per-recipient notification for a sent message.
     *
     * @param  array<int|string>  $recipientIds
     */
    private function syncRecipients(InternalMail $mail, array $recipientIds, bool $isDraft): void
    {
        $recipientIds = array_values(array_unique(array_filter(
            array_map('intval', $recipientIds),
            fn ($id) => $id > 0
        )));

        foreach ($recipientIds as $recipientId) {
            InternalMailRecipient::create([
                'mail_id'      => $mail->id,
                'recipient_id' => $recipientId,
            ]);

            // #180: a sent message generates a notification for the recipient.
            if (! $isDraft) {
                \App\Models\Notification::create([
                    'user_id'     => $recipientId,
                    'type'        => 'message_received',
                    'title'       => __('mailbox.notify.title'),
                    'body'        => $mail->subject,
                    'icon'        => 'la la-envelope',
                    'color'       => $mail->importance === 'urgent' ? 'danger' : ($mail->importance === 'important' ? 'warning' : 'info'),
                    'action_url'  => route('my.mailbox.show', $mail->id),
                    'action_text' => __('mailbox.notify.action'),
                ]);
            }
        }
    }

    /**
     * Load a draft the current user owns and may still edit. 404 if it isn't
     * the user's own unsent draft (or crosses a school boundary).
     */
    private function loadOwnDraft(int $mail): InternalMail
    {
        $user    = auth()->user();
        $message = InternalMail::with('recipients')->findOrFail($mail);

        abort_unless($message->sender_id === $user->id, 403);
        abort_unless($message->is_draft, 404);
        abort_if($message->school_id !== $this->activeSchoolId() && ! $user->isSuperAdmin(), 404);

        return $message;
    }

    private function prefixSubject(string $subject, string $prefix): string
    {
        return Str::startsWith($subject, $prefix) ? $subject : "{$prefix} {$subject}";
    }

    private function quoteOriginal(InternalMail $message): string
    {
        $sender = $message->sender->name ?? '—';
        $when   = optional($message->created_at)->format('Y-m-d H:i');

        return "\n\n----------\n"
            . __('mailbox.from') . ": {$sender} | {$when}\n"
            . $message->body;
    }

    /**
     * Load a mail and assert the current user may see it: sender or recipient,
     * and within the user's school (super-admin is global). Returns
     * [InternalMail, isSender, ?recipientRow].
     *
     * @return array{0:InternalMail,1:bool,2:?InternalMailRecipient}
     */
    private function authorizeMail(int $mail): array
    {
        $user   = auth()->user();
        $userId = $user->id;

        $message = InternalMail::query()
            ->with(['sender:id,name', 'recipients.recipient:id,name', 'relatedStudent:id,name'])
            ->findOrFail($mail);

        // Tenant scope: a non super-admin may never read another school's mail.
        abort_if($message->school_id !== $this->activeSchoolId() && ! $user->isSuperAdmin(), 404);

        $isSender     = $message->sender_id === $userId;
        $recipientRow = InternalMailRecipient::where('mail_id', $mail)
            ->where('recipient_id', $userId)
            ->first();

        abort_unless($isSender || $recipientRow !== null, 403);

        return [$message, $isSender, $recipientRow];
    }

    private function findRecipientRow(int $mailId): InternalMailRecipient
    {
        $row = InternalMailRecipient::where('mail_id', $mailId)
            ->where('recipient_id', auth()->id())
            ->first();

        abort_unless($row !== null, 403);

        return $row;
    }
}
