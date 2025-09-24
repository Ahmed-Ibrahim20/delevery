<?php

namespace App\Events;

use App\Models\Complaint;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Complaint $complaint;

    /**
     * Create a new event instance.
     */
    public function __construct(Complaint $complaint)
    {
        $this->complaint = $complaint;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('role.admin'), // قناة خاصة للأدمن
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'complaint.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'complaint_id' => $this->complaint->id,
            'title' => $this->complaint->title,
            'description' => $this->complaint->description,
            'user_info' => [
                'name' => $this->complaint->user->name ?? 'غير محدد',
                'phone' => $this->complaint->user->phone ?? 'غير محدد',
            ],
            'created_at' => $this->complaint->created_at->toISOString(),
            'notification' => [
                'title' => 'شكوى جديدة',
                'message' => 'شكوى جديدة من ' . ($this->complaint->user->name ?? 'مستخدم') . ': ' . $this->complaint->title,
                'type' => 'complaint_created'
            ]
        ];
    }
}
