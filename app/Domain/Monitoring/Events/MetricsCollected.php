<?php

namespace App\Domain\Monitoring\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MetricsCollected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $deviceType,
        public readonly string $deviceId,
        public readonly array $metrics,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'metrics.collected';
    }

    public function broadcastWith(): array
    {
        return [
            'device_type' => $this->deviceType,
            'device_id' => $this->deviceId,
            'metrics' => $this->metrics,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
