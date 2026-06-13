<?php

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Support\Http\Requests\StoreReplyRequest;
use App\Modules\Support\Http\Requests\StoreTicketRequest;
use App\Modules\Support\Repositories\Contracts\SupportTicketRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserSupportController extends Controller
{
    use HasSchoolScope;

    public function __construct(private SupportTicketRepository $tickets) {}

    public function index(): View
    {
        $tickets = $this->tickets->getUserTickets(
            $this->activeSchoolId(),
            auth()->id()
        );

        // Status counters for the user's own tickets (#186).
        $base = \App\Models\SupportTicket::query()
            ->where('school_id', $this->activeSchoolId())
            ->where('created_by', auth()->id());
        $counts = [
            'all'         => (clone $base)->count(),
            'open'        => (clone $base)->where('status', 'open')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
            'resolved'    => (clone $base)->where('status', 'resolved')->count(),
            'closed'      => (clone $base)->where('status', 'closed')->count(),
        ];

        return view('support.user.index', compact('tickets', 'counts'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $children = $user->isParent()
            ? $user->children()->get(['users.id', 'users.name'])
            : collect();

        return view('support.user.create', compact('children'));
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $ticket = $this->tickets->create([
            'school_id'          => $this->activeSchoolId(),
            'created_by'         => $user->id,
            'related_student_id' => $request->validated('related_student_id'),
            'creator_role'       => $user->roles->first()?->slug ?? 'user',
            'category'           => $request->validated('category'),
            'subject'            => $request->validated('subject'),
            'body'               => $request->validated('body'),
            'priority'           => $request->validated('priority') ?? 'normal',
            'status'             => 'open',
        ]);

        return redirect()
            ->route('my.support.show', $ticket->id)
            ->with('success', __('support.flash_created'));
    }

    public function show(int $ticket): View
    {
        $ticket = $this->tickets->find($ticket);
        abort_if(! $ticket, 404);
        abort_if($ticket->created_by !== auth()->id(), 403);

        return view('support.user.show', compact('ticket'));
    }

    public function reply(StoreReplyRequest $request, int $ticket): RedirectResponse
    {
        $ticket = $this->tickets->find($ticket);
        abort_if(! $ticket, 404);
        abort_if($ticket->created_by !== auth()->id(), 403);

        $this->tickets->addReply($ticket->id, [
            'user_id'  => auth()->id(),
            'body'     => $request->validated('body'),
            'is_staff' => 0,
        ]);

        return redirect()
            ->route('my.support.show', $ticket->id)
            ->with('success', __('support.flash_reply_sent'));
    }
}
