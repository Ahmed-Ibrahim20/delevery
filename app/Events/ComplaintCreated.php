<?php

namespace App\Events;

use App\Models\Complaint;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ComplaintCreated
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
}
