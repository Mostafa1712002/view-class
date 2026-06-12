<?php

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Support\Http\Requests\StoreReplyRequest;
use App\Modules\Support\Repositories\Contracts\SupportTicketRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSupportController extends Controller
{
    use HasSchoolScope;

    public function __construct(private SupportTicketRepository $tickets) {}

    public function index(Request $request): View
    {
        $filters = [
            'status'   => $request->get('status'),
            'priority' => $request->get('priority'),
            'category' => $request->get('category'),
        ];

        $tickets = $this->tickets->getSchoolTickets($this->activeSchoolId(), $filters);

        return view('support.admin.index', compact('tickets', 'filters'));
    }

    public function show(int $ticket): View
    {
        $ticket = $this->tickets->find($ticket);
        abort_if(! $ticket, 404);

        $user = auth()->user();
        if (! $user->isSuperAdmin()) {
            abort_if($ticket->school_id !== $this->activeSchoolId(), 403);
        }

        return view('support.admin.show', compact('ticket'));
    }

    public function reply(StoreReplyRequest $request, int $ticket): RedirectResponse
    {
        $ticket = $this->tickets->find($ticket);
        abort_if(! $ticket, 404);

        $user = auth()->user();
        if (! $user->isSuperAdmin()) {
            abort_if($ticket->school_id !== $this->activeSchoolId(), 403);
        }

        $this->tickets->addReply($ticket->id, [
            'user_id'  => auth()->id(),
            'body'     => $request->validated('body'),
            'is_staff' => 1,
        ]);

        return redirect()
            ->route('admin.support.show', $ticket->id)
            ->with('success', __('support.flash_reply_sent'));
    }

    public function assign(Request $request, int $ticket): RedirectResponse
    {
        $request->validate([
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);

        $ticket = $this->tickets->find($ticket);
        abort_if(! $ticket, 404);

        $user = auth()->user();
        if (! $user->isSuperAdmin()) {
            abort_if($ticket->school_id !== $this->activeSchoolId(), 403);
        }

        $this->tickets->assign($ticket->id, (int) $request->input('assigned_to'));

        return redirect()
            ->route('admin.support.show', $ticket->id)
            ->with('success', __('support.flash_assigned'));
    }

    public function updateStatus(Request $request, int $ticket): RedirectResponse
    {
        $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $ticket = $this->tickets->find($ticket);
        abort_if(! $ticket, 404);

        $user = auth()->user();
        if (! $user->isSuperAdmin()) {
            abort_if($ticket->school_id !== $this->activeSchoolId(), 403);
        }

        $this->tickets->updateStatus($ticket->id, $request->input('status'));

        return redirect()
            ->route('admin.support.show', $ticket->id)
            ->with('success', __('support.flash_status_updated'));
    }
}
