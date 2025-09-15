<?php

/// Author: Tan Huei Qing

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use App\Http\Resources\NotificationResource;
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

    // ==================== API METHODS ====================

    /**
     * API: Display a listing of notifications
     */
    public function apiIndex(Request $request)
    {
        $user = Auth::user();
        $perPage = $request->get('per_page', 15);
        $result = $this->notificationService->getUserNotifications($user, $perPage);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => NotificationResource::collection($result['notifications']),
                'meta' => [
                    'current_page' => $result['notifications']->currentPage(),
                    'last_page' => $result['notifications']->lastPage(),
                    'per_page' => $result['notifications']->perPage(),
                    'total' => $result['notifications']->total(),
                ]
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * API: Mark notification as read
     */
    public function apiMarkAsRead(Notification $notification)
    {
        $user = Auth::user();
        $result = $this->notificationService->markAsRead($notification, $user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new NotificationResource($notification->fresh())
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * API: Mark all notifications as read
     */
    public function apiMarkAllAsRead()
    {
        $user = Auth::user();
        $result = $this->notificationService->markAllAsRead($user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * API: Delete a notification
     */
    public function apiDestroy(Notification $notification)
    {
        $user = Auth::user();
        $result = $this->notificationService->deleteNotification($notification, $user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message']
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['error']
        ], 400);
    }

    /**
     * API: Get unread notifications count
     */
    public function apiUnreadCount()
    {
        $user = Auth::user();
        $result = $this->notificationService->getUnreadCount($user);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'data' => ['count' => $result['count']]
            ], 200);
        }

        return response()->json([
            'success' => false,
            'data' => ['count' => 0]
        ], 200);
    }
}
