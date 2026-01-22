<?php

namespace App\Events\StudentEnquiryReply;

use App\Enums\PRFLiveEvent;
use App\Http\Resources\StudentEnquiryReply\Resource;
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
        private string $studentEnquiryUlid,
    ) {}

    public PRFLiveEvent $event = PRFLiveEvent::STUDENT_ENQUIRY_REPLY_CREATED;

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.StudentEnquiry.'.$this->studentEnquiryUlid),
        ];
    }
}
