<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Http\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendUserNotification
{

    protected NotificationService $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(UserRegistered $event): void
    {
        try {
            // إشعار للأدمن بتسجيل مستخدم جديد
            $this->notificationService->createUserNotification(
                $event->user,
                Notification::TYPE_USER_REGISTRATION
            );
            
            Log::info('User registration notification sent', ['user_id' => $event->user->id]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send user notification: ' . $e->getMessage(), [
                'user_id' => $event->user->id
            ]);
        }
    }
}
