<?php

namespace App\Domain\Device\Events;

use App\Domain\Device\Models\Olt;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OltStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Olt $olt,
        public readonly string $newStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'olt.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'olt_id' => $this->olt->id,
            'name' => $this->olt->name,
            'status' => $this->newStatus,
            'ip_address' => $this->olt->ip_address,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
