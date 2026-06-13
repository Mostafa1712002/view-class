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

        // Handle file attachment
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store("mails/{$mail->id}", 'public');
            $mail->update(['attachment_path' => $path]);
        }

        // Create recipient rows
        $recipientIds = array_filter((array) $request->validated('to'), fn ($id) => ! empty($id));

        foreach ($recipientIds as $recipientId) {
            InternalMailRecipient::create([
                'mail_id'      => $mail->id,
                'recipient_id' => (int) $recipientId,
            ]);
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
        $userId  = auth()->id();
        $message = InternalMail::withTrashed()
            ->with(['sender:id,name', 'recipients.recipient:id,name', 'relatedStudent:id,name'])
            ->findOrFail($mail);

        $isSender    = $message->sender_id === $userId;
        $recipientRow = InternalMailRecipient::where('mail_id', $mail)
            ->where('recipient_id', $userId)
            ->first();

        abort_unless($isSender || $recipientRow !== null, 403);

        // Mark as read for the recipient
        if ($recipientRow) {
            $this->mailbox->markRead($mail, $userId);
            $recipientRow->refresh();
        }

        return view('mailbox.show', compact('message', 'isSender', 'recipientRow'));
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
