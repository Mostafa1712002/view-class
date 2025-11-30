<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display all notifications for the authenticated user
     */
    public function index(): View
    {
        $notifications = auth()->user()->customNotifications()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $unreadCount = auth()->user()->customNotifications()->unread()->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    /**
     * Get unread notifications count (for AJAX)
     */
    public function unreadCount(): JsonResponse
    {
        $count = auth()->user()->customNotifications()->unread()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Get latest notifications (for dropdown)
     */
    public function latest(): JsonResponse
    {
        $notifications = auth()->user()->customNotifications()
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'icon' => $notification->getIcon(),
                    'color' => $notification->color,
                    'action_url' => $notification->action_url,
                    'is_read' => $notification->isRead(),
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });

        $unreadCount = auth()->user()->customNotifications()->unread()->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark a notification as read
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->customNotifications()
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403);
        }

        $notification->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Delete all read notifications
     */
    public function clearRead(): JsonResponse
    {
        auth()->user()->customNotifications()
            ->read()
            ->delete();

        return response()->json(['success' => true]);
    }
}
