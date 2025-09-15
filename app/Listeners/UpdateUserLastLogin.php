<?php

/// Author: Pooi Wai Guan

namespace App\Listeners;

use App\Events\UserLoggedIn;
use Illuminate\Support\Facades\DB;

class UpdateUserLastLogin
{
    /**
     * Handle user login events.
     */
    public function handle(UserLoggedIn $event): void
    {
        // Update the user's last login timestamp
        $event->user->update([
            'last_login_at' => now()
        ]);

        // Log the update
        \Log::info('Updated last login time for user', [
            'user_id' => $event->user->id,
            'last_login_at' => now()->toISOString()
        ]);
    }
}
