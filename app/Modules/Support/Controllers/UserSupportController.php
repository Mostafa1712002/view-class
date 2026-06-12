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

        return view('support.user.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('support.user.create');
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $user = auth()->user();

        $ticket = $this->tickets->create([
            'school_id'    => $this->activeSchoolId(),
            'created_by'   => $user->id,
            'creator_role' => $user->roles->first()?->slug ?? 'user',
            'category'     => $request->validated('category'),
            'subject'      => $request->validated('subject'),
            'body'         => $request->validated('body'),
            'priority'     => $request->validated('priority') ?? 'normal',
            'status'       => 'open',
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
