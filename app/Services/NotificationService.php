<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create a new notification
     */
    public function createNotification(int $userId, string $message, string $type = 'info'): array
    {
        try {
            $notification = Notification::create([
                'user_id' => $userId,
                'message' => $message,
                'type' => $type,
                'is_read' => false,
            ]);

            return [
                'success' => true,
                'notification' => $notification,
                'message' => 'Notification created successfully.'
            ];

        } catch (\Exception $e) {
            Log::error('Create Notification Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to create notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's notifications with pagination
     */
    public function getUserNotifications(User $user, int $perPage = 10): array
    {
        try {
            $notifications = $user->notifications()
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return [
                'success' => true,
                'notifications' => $notifications
            ];

        } catch (\Exception $e) {
            Log::error('Get User Notifications Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve notifications: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Notification $notification, User $user): array
    {
        try {
            // Check if user owns this notification
            if ($notification->user_id !== $user->id) {
                return [
                    'success' => false,
                    'error' => 'Unauthorized access to notification.'
                ];
            }

            $notification->markAsRead();

            return [
                'success' => true,
                'message' => 'Notification marked as read.'
            ];

        } catch (\Exception $e) {
            Log::error('Mark Notification as Read Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to mark notification as read: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(User $user): array
    {
        try {
            $user->notifications()->update(['is_read' => true]);

            return [
                'success' => true,
                'message' => 'All notifications marked as read.'
            ];

        } catch (\Exception $e) {
            Log::error('Mark All Notifications as Read Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to mark all notifications as read: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a notification
     */
    public function deleteNotification(Notification $notification, User $user): array
    {
        try {
            // Check if user owns this notification
            if ($notification->user_id !== $user->id) {
                return [
                    'success' => false,
                    'error' => 'Unauthorized access to notification.'
                ];
            }

            $notification->delete();

            return [
                'success' => true,
                'message' => 'Notification deleted.'
            ];

        } catch (\Exception $e) {
            Log::error('Delete Notification Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to delete notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get unread notifications count for a user
     */
    public function getUnreadCount(User $user): array
    {
        try {
            $count = $user->notifications()->where('is_read', false)->count();

            return [
                'success' => true,
                'count' => $count
            ];

        } catch (\Exception $e) {
            Log::error('Get Unread Count Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to get unread count: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send appointment notification
     */
    public function sendAppointmentNotification(User $user, string $message, string $type = 'info'): array
    {
        return $this->createNotification($user->id, $message, $type);
    }

    /**
     * Send payment notification
     */
    public function sendPaymentNotification(User $user, string $message, string $type = 'success'): array
    {
        return $this->createNotification($user->id, $message, $type);
    }

    /**
     * Send system notification to all users
     */
    public function sendSystemNotification(string $message, string $type = 'info'): array
    {
        try {
            $users = User::all();
            $createdCount = 0;

            foreach ($users as $user) {
                $result = $this->createNotification($user->id, $message, $type);
                if ($result['success']) {
                    $createdCount++;
                }
            }

            return [
                'success' => true,
                'message' => "System notification sent to {$createdCount} users."
            ];

        } catch (\Exception $e) {
            Log::error('Send System Notification Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to send system notification: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send notification to users by role
     */
    public function sendNotificationToRole(string $role, string $message, string $type = 'info'): array
    {
        try {
            $users = User::where('role', $role)->get();
            $createdCount = 0;

            foreach ($users as $user) {
                $result = $this->createNotification($user->id, $message, $type);
                if ($result['success']) {
                    $createdCount++;
                }
            }

            return [
                'success' => true,
                'message' => "Notification sent to {$createdCount} {$role}s."
            ];

        } catch (\Exception $e) {
            Log::error('Send Notification to Role Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to send notification to role: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(): array
    {
        try {
            $totalNotifications = Notification::count();
            $unreadNotifications = Notification::where('is_read', false)->count();
            $readNotifications = Notification::where('is_read', true)->count();
            $todayNotifications = Notification::whereDate('created_at', today())->count();

            return [
                'success' => true,
                'stats' => [
                    'total' => $totalNotifications,
                    'unread' => $unreadNotifications,
                    'read' => $readNotifications,
                    'today' => $todayNotifications,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Get Notification Stats Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to get notification statistics: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $daysOld = 30): array
    {
        try {
            $cutoffDate = now()->subDays($daysOld);
            $deletedCount = Notification::where('created_at', '<', $cutoffDate)
                ->where('is_read', true)
                ->delete();

            return [
                'success' => true,
                'message' => "Cleaned up {$deletedCount} old notifications."
            ];

        } catch (\Exception $e) {
            Log::error('Cleanup Old Notifications Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to cleanup old notifications: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get notifications by type
     */
    public function getNotificationsByType(User $user, string $type, int $perPage = 10): array
    {
        try {
            $notifications = $user->notifications()
                ->where('type', $type)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return [
                'success' => true,
                'notifications' => $notifications
            ];

        } catch (\Exception $e) {
            Log::error('Get Notifications by Type Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to retrieve notifications by type: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk mark notifications as read
     */
    public function bulkMarkAsRead(User $user, array $notificationIds): array
    {
        try {
            $updatedCount = $user->notifications()
                ->whereIn('id', $notificationIds)
                ->update(['is_read' => true]);

            return [
                'success' => true,
                'message' => "Marked {$updatedCount} notifications as read."
            ];

        } catch (\Exception $e) {
            Log::error('Bulk Mark as Read Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to bulk mark notifications as read: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk delete notifications
     */
    public function bulkDelete(User $user, array $notificationIds): array
    {
        try {
            $deletedCount = $user->notifications()
                ->whereIn('id', $notificationIds)
                ->delete();

            return [
                'success' => true,
                'message' => "Deleted {$deletedCount} notifications."
            ];

        } catch (\Exception $e) {
            Log::error('Bulk Delete Notifications Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to bulk delete notifications: ' . $e->getMessage()
            ];
        }
    }
}
