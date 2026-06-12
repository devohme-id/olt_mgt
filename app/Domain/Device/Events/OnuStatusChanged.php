<?php

namespace App\Domain\Device\Events;

use App\Domain\Device\Models\Onu;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OnuStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Onu $onu,
        public readonly string $previousStatus,
        public readonly string $newStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'onu.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'onu_id' => $this->onu->id,
            'olt_id' => $this->onu->olt_id,
            'onu_index' => $this->onu->onu_index,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'serial_number' => $this->onu->serial_number,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
