<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAccepted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        // إشعار للمحل الذي أضاف الطلب
        if ($this->order->addedBy) {
            $channels[] = new PrivateChannel('user.' . $this->order->addedBy->id);
        }
        
        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.accepted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'customer_name' => $this->order->customer_name,
            'delivery_info' => [
                'driver_name' => $this->order->delivery->name ?? 'غير محدد',
                'driver_phone' => $this->order->delivery->phone ?? 'غير محدد',
            ],
            'notification' => [
                'title' => 'تم قبول الطلب',
                'message' => "تم قبول طلب رقم #{$this->order->id} من قبل السائق",
                'type' => 'order_accepted'
            ]
        ];
    }
}
