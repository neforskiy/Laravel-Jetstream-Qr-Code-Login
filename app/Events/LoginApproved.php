<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class LoginApproved implements ShouldBroadcast
{
    public function __construct(
        public string $uuid
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('login.'.$this->uuid),
        ];
    }

    public function broadcastAs(): string
    {
        return 'login.approved';
    }

    public function broadcastWith()
    {
        return [
            'uuid' => $this->uuid,
        ];
    }
}
