<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class LoginApproved implements ShouldBroadcast
{
    public function __construct(
        public string $uuid
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('login.'.$this->uuid),
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
