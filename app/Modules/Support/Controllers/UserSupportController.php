<?php

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\SupportTicket;
use App\Modules\Support\Http\Requests\StoreReplyRequest;
use App\Modules\Support\Http\Requests\StoreTicketRequest;
use App\Modules\Support\Repositories\Contracts\SupportTicketRepository;
use App\Modules\Support\Services\SupportNotifier;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class UserSupportController extends Controller
{
    use HasSchoolScope;

    public function __construct(
        private SupportTicketRepository $tickets,
        private SupportNotifier $notifier,
    ) {}

    public function index(): View
    {
        $tickets = $this->tickets->getUserTickets(
            $this->activeSchoolId(),
            auth()->id()
        );

        // Status counters for the user's own tickets (#186).
        $schoolId = $this->activeSchoolId();
        $base = SupportTicket::query()
            ->when($schoolId !== null, fn ($q) => $q->where('school_id', $schoolId))
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
            'type'               => $request->validated('type'),
            'category'           => $request->validated('category'),
            'department'         => $request->validated('department'),
            'subject'            => $request->validated('subject'),
            'body'               => $request->validated('body'),
            'problem_url'        => $request->validated('problem_url'),
            'priority'           => $request->validated('priority') ?? 'normal',
            'status'             => 'open',
            'attachment_path'    => $this->storeAttachment($request),
        ]);

        ActivityLog::logCreate($ticket, 'إنشاء تذكرة دعم');
        $this->notifier->ticketCreated($ticket);

        return redirect()
            ->route('my.support.show', $ticket->id)
            ->with('success', __('support.flash_created'));
    }

    public function show(int $ticket): View
    {
        $ticket = $this->resolveOwn($ticket);

        return view('support.user.show', compact('ticket'));
    }

    public function reply(StoreReplyRequest $request, int $ticket): RedirectResponse
    {
        $ticket = $this->resolveOwn($ticket);
        abort_if(in_array($ticket->status, ['resolved', 'closed'], true), 403);

        $this->tickets->addReply($ticket->id, [
            'user_id'         => auth()->id(),
            'body'            => $request->validated('body'),
            'is_staff'        => 0,
            'attachment_path' => $this->storeAttachment($request),
        ]);

        ActivityLog::log('support.reply', 'رد المستخدم على تذكرة دعم', $ticket);
        $this->notifier->userReplied($ticket->fresh());

        return redirect()
            ->route('my.support.show', $ticket->id)
            ->with('success', __('support.flash_reply_sent'));
    }

    public function attachment(int $ticket)
    {
        $ticket = $this->resolveOwn($ticket);
        abort_if(! $ticket->attachment_path, 404);
        abort_unless(Storage::disk('local')->exists($ticket->attachment_path), 404);

        return Storage::disk('local')->download($ticket->attachment_path);
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    private function resolveOwn(int $id): SupportTicket
    {
        $ticket = $this->tickets->find($id);
        abort_if(! $ticket, 404);
        abort_if($ticket->created_by !== auth()->id(), 403);

        return $ticket;
    }

    private function storeAttachment(Request $request): ?string
    {
        if (! $request->hasFile('attachment')) {
            return null;
        }

        return $request->file('attachment')->store('support', 'local');
    }
}
