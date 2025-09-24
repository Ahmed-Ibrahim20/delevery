<?php

namespace App\Providers;

use App\Events\OrderCreated;
use App\Events\OrderAccepted;
use App\Events\OrderDelivered;
use App\Events\UserRegistered;
use App\Events\ComplaintCreated;
use App\Listeners\SendOrderNotification;
use App\Listeners\SendUserNotification;
use App\Listeners\SendComplaintNotification;
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

        // Order Events
        OrderCreated::class => [
            SendOrderNotification::class,
        ],
        OrderAccepted::class => [
            SendOrderNotification::class,
        ],
        OrderDelivered::class => [
            SendOrderNotification::class,
        ],

        // User Events
        UserRegistered::class => [
            SendUserNotification::class,
        ],

        // Complaint Events
        ComplaintCreated::class => [
            SendComplaintNotification::class,
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
