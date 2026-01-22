<?php

namespace App\Events\AnnouncementGroup;

use App\Enums\PRFLiveEvent;
use App\Http\Resources\AnnouncementGroup\Resource;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
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
        public string $groupUlid,
    ) {}

    public PRFLiveEvent $event = PRFLiveEvent::ANNOUNCEMENT_GROUP_CREATED;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('App.Models.Group.'.$this->groupUlid),
        ];
    }
}
