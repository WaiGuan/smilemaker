<?php

/// Author: Pooi Wai Guan

namespace App\Listeners;

use App\Events\UserLoggedIn;
use App\Events\UserRegistered;
use App\Events\UserLoggedOut;
use Illuminate\Support\Facades\Log;

class LogAuthenticationActivity
{
    /**
     * Handle user login events.
     */
    public function handleLogin(UserLoggedIn $event): void
    {
        Log::info('User logged in successfully', [
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
            'user_role' => $event->user->role,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Handle user registration events.
     */
    public function handleRegistration(UserRegistered $event): void
    {
        Log::info('New user registered', [
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
            'user_role' => $event->user->role,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Handle user logout events.
     */
    public function handleLogout(UserLoggedOut $event): void
    {
        Log::info('User logged out', [
            'user_id' => $event->user->id,
            'user_email' => $event->user->email,
            'user_role' => $event->user->role,
            'timestamp' => now()->toISOString()
        ]);
    }
}
