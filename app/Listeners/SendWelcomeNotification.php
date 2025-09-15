<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendWelcomeNotification
{
    /**
     * Handle user registration events.
     */
    public function handle(UserRegistered $event): void
    {
        // Create a welcome notification for the new user
        Notification::create([
            'user_id' => $event->user->id,
            'title' => 'Welcome to Our Dental Clinic!',
            'message' => 'Thank you for registering with us. We look forward to providing you with excellent dental care.',
            'type' => 'welcome',
            'is_read' => false
        ]);

        Log::info('Welcome notification sent to new user', [
            'user_id' => $event->user->id,
            'user_email' => $event->user->email
        ]);
    }
}
