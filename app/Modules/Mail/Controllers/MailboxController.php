<?php

namespace App\Modules\Mail\Controllers;

use App\Http\Controllers\Controller;
use App\Models\InternalMail;
use App\Models\InternalMailRecipient;
use App\Models\User;
use App\Modules\Mail\Http\Requests\StoreMailRequest;
use App\Modules\Mail\Repositories\Contracts\MailboxRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MailboxController extends Controller
{
    use HasSchoolScope;

    private const ALLOWED_FOLDERS = ['inbox', 'sent', 'drafts', 'starred', 'important', 'task', 'archive', 'trash'];

    public function __construct(private MailboxRepository $mailbox) {}

    public function index(Request $request, string $folder = 'inbox'): View
    {
        abort_unless(in_array($folder, self::ALLOWED_FOLDERS, true), 404);

        $userId = auth()->id();

        $filters = array_filter([
            'importance' => $request->get('importance'),
            'unread'     => $request->boolean('unread') ? true : null,
        ], fn ($v) => $v !== null);

        $messages = $this->mailbox->getFolder($userId, $folder, $filters);
        $counts   = $this->mailbox->getFolderCounts($userId);

        return view('mailbox.index', compact('folder', 'messages', 'counts', 'filters'));
    }

    public function create(): View
    {
        $schoolId = $this->activeSchoolId();
        $user     = auth()->user();

        $recipients = User::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->whereNull('deleted_at')
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->limit(2000)
            ->get(['id', 'name']);

        $children = $user->isParent()
            ? $user->children()->get(['users.id', 'users.name'])
            : collect();

        return view('mailbox.compose', compact('recipients', 'children'));
    }

    public function store(StoreMailRequest $request): RedirectResponse
    {
        $isDraft = $request->input('action') === 'draft';
        $user    = auth()->user();

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

        // Create recipient rows
        $recipientIds = array_filter((array) $request->validated('to'), fn ($id) => ! empty($id));

        foreach ($recipientIds as $recipientId) {
            InternalMailRecipient::create([
                'mail_id'      => $mail->id,
                'recipient_id' => (int) $recipientId,
            ]);

            // #180: a sent message generates a notification for the recipient.
            if (! $isDraft) {
                \App\Models\Notification::create([
                    'user_id'     => (int) $recipientId,
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

        if ($isDraft) {
            return redirect()
                ->route('my.mailbox.folder', 'drafts')
                ->with('success', __('mailbox.draft_saved'));
        }

        return redirect()
            ->route('my.mailbox.folder', 'sent')
            ->with('success', __('mailbox.sent_success'));
    }

    public function show(int $mail): View
    {
        [$message, $isSender, $recipientRow] = $this->authorizeMail($mail);

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
        [$message] = $this->authorizeMail($mail);

        abort_unless($message->attachment_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($message->attachment_path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $message->attachment_path,
            basename($message->attachment_path),
            ['Content-Disposition' => 'attachment']
        );
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
        $row = $this->findRecipientRow($mail);
        $row->update(['archived' => true]);

        return back()->with('success', __('mailbox.archived_action'));
    }

    public function unarchive(int $mail): RedirectResponse
    {
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
        $userId = auth()->id();
        $row    = InternalMailRecipient::where('mail_id', $mail)
            ->where('recipient_id', $userId)
            ->first();

        if ($row) {
            $row->delete();
        }

        // If no recipients remain and the sender is deleting, clean up the mail record
        $mailRecord = InternalMail::find($mail);
        if ($mailRecord && $mailRecord->sender_id === $userId) {
            $remaining = InternalMailRecipient::where('mail_id', $mail)->count();
            if ($remaining === 0) {
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

    private function findRecipientRow(int $mailId): InternalMailRecipient
    {
        $row = InternalMailRecipient::where('mail_id', $mailId)
            ->where('recipient_id', auth()->id())
            ->first();

        abort_unless($row !== null, 403);

        return $row;
    }
}
