<?php

namespace App\Events\LessonMember;

use App\Enums\PRFLiveEvent;
use App\Http\Resources\LessonModule\Resource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class Created implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Resource $data,
        private string $userUlid,
    ) {}

    public PRFLiveEvent $event = PRFLiveEvent::LESSON_MEMBER_UPDATED;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.'.$this->userUlid),
        ];
    }
}
