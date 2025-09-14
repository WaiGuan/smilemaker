<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Show user's notifications
     */
    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        // Check if user owns this notification
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to notification.');
        }

        $notification->markAsRead();

        return redirect()->back()
            ->with('success', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->notifications()->update(['is_read' => true]);

        return redirect()->back()
            ->with('success', 'All notifications marked as read.');
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification)
    {
        // Check if user owns this notification
        if ($notification->user_id !== Auth::id()) {
            abort(403, 'Unauthorized access to notification.');
        }

        $notification->delete();

        return redirect()->back()
            ->with('success', 'Notification deleted.');
    }

    /**
     * Get unread notifications count (for AJAX)
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $count = $user->notifications()->where('is_read', false)->count();

        return response()->json(['count' => $count]);
    }
}
