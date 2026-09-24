<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OccupancyUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $classroom_id;
    public int $current_occupancy;
    public int $capacity;

    public function __construct(int $classroom_id, int $current_occupancy, int $capacity)
    {
        $this->classroom_id = $classroom_id;
        $this->current_occupancy = $current_occupancy;
        $this->capacity = $capacity;
    }

    public function broadcastOn(): Channel
    {
        return new Channel('classroom.'.$this->classroom_id);
    }

    public function broadcastWith(): array
    {
        return [
            'classroom_id' => $this->classroom_id,
            'current_occupancy' => $this->current_occupancy,
            'capacity' => $this->capacity,
        ];
    }
}
