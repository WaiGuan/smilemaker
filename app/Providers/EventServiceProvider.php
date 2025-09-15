<?php

/// Author: Foo Tek Sian

namespace App\Providers;

use App\Events\UserLoggedIn;
use App\Events\UserRegistered;
use App\Events\UserLoggedOut;
use App\Listeners\LogAuthenticationActivity;
use App\Listeners\UpdateUserLastLogin;
use App\Listeners\SendWelcomeNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Custom authentication events
        UserLoggedIn::class => [
            LogAuthenticationActivity::class . '@handleLogin',
            UpdateUserLastLogin::class,
        ],

        UserRegistered::class => [
            LogAuthenticationActivity::class . '@handleRegistration',
            SendWelcomeNotification::class,
        ],

        UserLoggedOut::class => [
            LogAuthenticationActivity::class . '@handleLogout',
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
