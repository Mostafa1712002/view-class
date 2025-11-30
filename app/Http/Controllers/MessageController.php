<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MessageController extends Controller
{
    /**
     * Display all conversations
     */
    public function index(): View
    {
        $user = auth()->user();

        $conversations = Conversation::forUser($user)
            ->withUnread($user)
            ->with(['participants', 'latestMessage.sender'])
            ->orderBy('last_message_at', 'desc')
            ->paginate(20);

        $totalUnread = $conversations->sum('unread_count');

        return view('messages.index', compact('conversations', 'totalUnread'));
    }

    /**
     * Show form to compose new message
     */
    public function create(): View
    {
        $user = auth()->user();

        // Get users that can be messaged based on role
        if ($user->isSuperAdmin()) {
            $users = User::where('id', '!=', $user->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } elseif ($user->isSchoolAdmin()) {
            $users = User::where('id', '!=', $user->id)
                ->where('school_id', $user->school_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } elseif ($user->isTeacher()) {
            // Teachers can message admins, other teachers, and parents of their students
            $users = User::where('id', '!=', $user->id)
                ->where('school_id', $user->school_id)
                ->where('is_active', true)
                ->whereHas('roles', function ($q) {
                    $q->whereIn('slug', ['school-admin', 'teacher', 'parent']);
                })
                ->orderBy('name')
                ->get();
        } else {
            // Students and parents can message teachers and admins
            $users = User::where('id', '!=', $user->id)
                ->where('school_id', $user->school_id)
                ->where('is_active', true)
                ->whereHas('roles', function ($q) {
                    $q->whereIn('slug', ['school-admin', 'teacher']);
                })
                ->orderBy('name')
                ->get();
        }

        return view('messages.create', compact('users'));
    }

    /**
     * Start a new conversation or get existing one
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $user = auth()->user();
        $recipient = User::findOrFail($request->recipient_id);

        // Start or get existing conversation
        $conversation = Conversation::startPrivate(
            $user,
            $recipient,
            $request->subject ?? 'محادثة خاصة'
        );

        // Handle attachment
        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('messages/attachments', 'public');
        }

        // Create message
        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $request->message,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        // Send notification to recipient
        Notification::sendMessageReceived($recipient, $user, $conversation);

        return redirect()->route('messages.show', $conversation)
            ->with('success', 'تم إرسال الرسالة بنجاح');
    }

    /**
     * Display a conversation
     */
    public function show(Conversation $conversation): View
    {
        $user = auth()->user();

        // Check if user is participant
        if (!$conversation->hasParticipant($user)) {
            abort(403);
        }

        // Mark conversation as read
        $conversation->markAsRead($user);

        // Load messages with sender
        $messages = $conversation->messages()
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('messages.show', compact('conversation', 'messages'));
    }

    /**
     * Send a message in an existing conversation
     */
    public function reply(Request $request, Conversation $conversation): RedirectResponse|JsonResponse
    {
        $user = auth()->user();

        // Check if user is participant
        if (!$conversation->hasParticipant($user)) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:10240',
        ]);

        // Handle attachment
        $attachmentPath = null;
        $attachmentName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $attachmentPath = $file->store('messages/attachments', 'public');
        }

        // Create message
        $message = $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $request->message,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        // Send notifications to other participants
        foreach ($conversation->participants as $participant) {
            if ($participant->id !== $user->id) {
                Notification::sendMessageReceived($participant, $user, $conversation);
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'body' => $message->body,
                    'sender' => $user->name,
                    'created_at' => $message->created_at->format('Y-m-d H:i'),
                    'has_attachment' => $message->hasAttachment(),
                    'attachment_name' => $message->attachment_name,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'تم إرسال الرسالة');
    }

    /**
     * Toggle mute status for a conversation
     */
    public function toggleMute(Conversation $conversation): JsonResponse
    {
        $user = auth()->user();

        if (!$conversation->hasParticipant($user)) {
            abort(403);
        }

        $isMuted = $conversation->toggleMute($user);

        return response()->json([
            'success' => true,
            'is_muted' => $isMuted,
        ]);
    }

    /**
     * Delete a message (soft delete)
     */
    public function destroyMessage(Message $message): JsonResponse
    {
        $user = auth()->user();

        // Only sender can delete their message
        if ($message->sender_id !== $user->id) {
            abort(403);
        }

        // Delete attachment if exists
        if ($message->hasAttachment()) {
            Storage::disk('public')->delete($message->attachment_path);
        }

        $message->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get unread count for messages (for AJAX)
     */
    public function unreadCount(): JsonResponse
    {
        $user = auth()->user();

        $count = Conversation::forUser($user)
            ->withUnread($user)
            ->get()
            ->sum('unread_count');

        return response()->json(['count' => $count]);
    }

    /**
     * Create a group conversation
     */
    public function createGroup(Request $request): RedirectResponse
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'participants' => 'required|array|min:2',
            'participants.*' => 'exists:users,id',
            'message' => 'required|string|max:5000',
        ]);

        $user = auth()->user();

        // Create group conversation
        $conversation = Conversation::startGroup(
            $user,
            $request->participants,
            $request->subject
        );

        // Create initial message
        $conversation->messages()->create([
            'sender_id' => $user->id,
            'body' => $request->message,
        ]);

        // Notify all participants
        foreach ($conversation->participants as $participant) {
            if ($participant->id !== $user->id) {
                Notification::sendMessageReceived($participant, $user, $conversation);
            }
        }

        return redirect()->route('messages.show', $conversation)
            ->with('success', 'تم إنشاء المجموعة بنجاح');
    }
}
