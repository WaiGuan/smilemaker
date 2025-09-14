<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    /**
     * Show user's notifications
     */
    public function index()
    {
        $user = Auth::user();
        $result = $this->notificationService->getUserNotifications($user, 10);

        if ($result['success']) {
            $notifications = $result['notifications'];
        } else {
            $notifications = collect();
        }

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification)
    {
        $user = Auth::user();
        $result = $this->notificationService->markAsRead($notification, $user);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['error']);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = Auth::user();
        $result = $this->notificationService->markAllAsRead($user);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['error']);
        }
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification)
    {
        $user = Auth::user();
        $result = $this->notificationService->deleteNotification($notification, $user);

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['error']);
        }
    }

    /**
     * Get unread notifications count (for AJAX)
     */
    public function unreadCount()
    {
        $user = Auth::user();
        $result = $this->notificationService->getUnreadCount($user);

        if ($result['success']) {
            return response()->json(['count' => $result['count']]);
        } else {
            return response()->json(['count' => 0]);
        }
    }
}
