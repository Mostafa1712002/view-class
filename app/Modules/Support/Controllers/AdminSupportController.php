<?php

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SupportTicket;
use App\Modules\Support\Http\Requests\StoreReplyRequest;
use App\Modules\Support\Repositories\Contracts\SupportTicketRepository;
use App\Modules\Support\Services\SupportNotifier;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminSupportController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private SupportTicketRepository $tickets,
        private SupportNotifier $notifier,
    ) {}

    public function index(Request $request): View
    {
        $schoolId = $this->scopedSchoolId();

        $filters = [
            'status'      => $request->get('status'),
            'priority'    => $request->get('priority'),
            'category'    => $request->get('category'),
            'type'        => $request->get('type'),
            'department'  => $request->get('department'),
            'reply_state' => $request->get('reply_state'),
        ];

        $tickets = $this->tickets->getSchoolTickets($schoolId, $filters);
        $counts  = $this->tickets->adminCounts($schoolId);

        return view('support.admin.index', compact('tickets', 'filters', 'counts'));
    }

    public function show(int $ticket): View
    {
        $ticket = $this->resolveScoped($ticket);

        return view('support.admin.show', compact('ticket'));
    }

    public function reply(StoreReplyRequest $request, int $ticket): RedirectResponse
    {
        $ticket = $this->resolveScoped($ticket);

        $this->tickets->addReply($ticket->id, [
            'user_id'         => auth()->id(),
            'body'            => $request->validated('body'),
            'is_staff'        => 1,
            'attachment_path' => $this->storeAttachment($request),
        ]);

        ActivityLog::log('support.reply', 'الرد على تذكرة دعم', $ticket);
        $this->notifier->staffReplied($ticket->fresh());

        return redirect()
            ->route('admin.support.show', $ticket->id)
            ->with('success', __('support.flash_reply_sent'));
    }

    public function assign(Request $request, int $ticket): RedirectResponse
    {
        $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $ticket   = $this->resolveScoped($ticket);
        $assignee = (int) $request->input('assigned_to');

        $this->tickets->assign($ticket->id, $assignee);

        ActivityLog::log('support.assign', 'تحويل تذكرة دعم', $ticket);
        $this->notifier->assigned($ticket->fresh(), $assignee);

        return redirect()
            ->route('admin.support.show', $ticket->id)
            ->with('success', __('support.flash_assigned'));
    }

    public function updateStatus(Request $request, int $ticket): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket = $this->resolveScoped($ticket);
        $status = $request->input('status');

        $this->tickets->updateStatus($ticket->id, $status, auth()->id());

        ActivityLog::log('support.change_status', 'تغيير حالة تذكرة دعم', $ticket);
        $this->notifier->statusChanged($ticket->fresh(), $status);

        return redirect()
            ->route('admin.support.show', $ticket->id)
            ->with('success', __('support.flash_status_updated'));
    }

    public function close(int $ticket): RedirectResponse
    {
        $ticket = $this->resolveScoped($ticket);

        $this->tickets->updateStatus($ticket->id, 'closed', auth()->id());

        ActivityLog::log('support.close', 'إغلاق تذكرة دعم', $ticket);
        $this->notifier->statusChanged($ticket->fresh(), 'closed');

        return redirect()
            ->route('admin.support.show', $ticket->id)
            ->with('success', __('support.flash_closed'));
    }

    public function reopen(int $ticket): RedirectResponse
    {
        $ticket = $this->resolveScoped($ticket);

        $this->tickets->updateStatus($ticket->id, 'open', auth()->id());

        ActivityLog::log('support.change_status', 'إعادة فتح تذكرة دعم', $ticket);
        $this->notifier->statusChanged($ticket->fresh(), 'open');

        return redirect()
            ->route('admin.support.show', $ticket->id)
            ->with('success', __('support.flash_reopened'));
    }

    public function destroy(int $ticket): RedirectResponse
    {
        $ticket = $this->resolveScoped($ticket);

        ActivityLog::logDelete($ticket, 'حذف تذكرة دعم');
        $this->tickets->delete($ticket->id);

        return redirect()
            ->route('admin.support.index')
            ->with('success', __('support.flash_deleted'));
    }

    /**
     * Download a ticket / reply attachment, gated by support.view_attachments.
     */
    public function attachment(int $ticket)
    {
        abort_unless(auth()->user()->canDo('support.view_attachments'), 403);

        $ticket = $this->resolveScoped($ticket);
        abort_if(! $ticket->attachment_path, 404);
        abort_unless(Storage::disk('public')->exists($ticket->attachment_path), 404);

        return Storage::disk('public')->download($ticket->attachment_path);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Load a ticket and enforce school scope (super-admin sees all schools).
     */
    private function resolveScoped(int $id): SupportTicket
    {
        $ticket = $this->tickets->find($id);
        abort_if(! $ticket, 404);

        $schoolId = $this->scopedSchoolId();
        if ($schoolId !== null) {
            abort_if($ticket->school_id !== $schoolId, 403);
        }

        return $ticket;
    }

    private function storeAttachment(Request $request): ?string
    {
        if (! $request->hasFile('attachment')) {
            return null;
        }

        return $request->file('attachment')->store('support', 'public');
    }
}
