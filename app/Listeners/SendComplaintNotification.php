<?php

namespace App\Listeners;

use App\Events\ComplaintCreated;
use App\Http\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendComplaintNotification
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
    public function handle(ComplaintCreated $event): void
    {
        try {
            Log::info('SendComplaintNotification listener started', [
                'complaint_id' => $event->complaint->id,
                'timestamp' => now()
            ]);

            // التحقق من عدم وجود إشعار مسبق لنفس الشكوى
            $existingNotification = Notification::where('notifiable_type', get_class($event->complaint))
                ->where('notifiable_id', $event->complaint->id)
                ->where('target_role', Notification::ROLE_ADMIN)
                ->first();

            if ($existingNotification) {
                Log::info('Notification already exists for this complaint', [
                    'complaint_id' => $event->complaint->id,
                    'existing_notification_id' => $existingNotification->id
                ]);
                return;
            }

            // إشعار للأدمن بشكوى جديدة
            $result = $this->notificationService->createComplaintNotification(
                $event->complaint,
                Notification::TYPE_COMPLAINT_CREATED
            );
            
            Log::info('Complaint created notification sent', [
                'complaint_id' => $event->complaint->id,
                'result' => $result['status'] ?? 'unknown'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to send complaint notification: ' . $e->getMessage(), [
                'complaint_id' => $event->complaint->id
            ]);
        }
    }
}
