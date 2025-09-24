<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\OrderAccepted;
use App\Events\OrderDelivered;
use App\Http\Services\NotificationService;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;

class SendOrderNotification
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
    public function handle($event): void
    {
        try {
            if ($event instanceof OrderCreated) {
                // إشعار للسائقين بطلب جديد
                $this->notificationService->createOrderNotification(
                    $event->order,
                    Notification::TYPE_ORDER_CREATED
                );
                
                Log::info('Order created notification sent', ['order_id' => $event->order->id]);
                
            } elseif ($event instanceof OrderAccepted) {
                // إشعار للمحل بقبول الطلب
                $this->notificationService->createOrderNotification(
                    $event->order,
                    Notification::TYPE_ORDER_ACCEPTED
                );
                
                Log::info('Order accepted notification sent', ['order_id' => $event->order->id]);
                
            } elseif ($event instanceof OrderDelivered) {
                // إشعار للمحل بتسليم الطلب
                $this->notificationService->createOrderNotification(
                    $event->order,
                    Notification::TYPE_ORDER_DELIVERED
                );
                
                Log::info('Order delivered notification sent', ['order_id' => $event->order->id]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order notification: ' . $e->getMessage(), [
                'event' => get_class($event),
                'order_id' => $event->order->id ?? null
            ]);
        }
    }
}
