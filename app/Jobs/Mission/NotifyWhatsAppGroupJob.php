<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
use App\Models\Member;
use App\Models\Mission;
use App\Models\MissionSubscription;
use App\Notifications\Mission\MissionWhatsAppGroupLinkedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;

#[Queue('high')]
#[Tries(3)]
class NotifyWhatsAppGroupJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Mission $mission,
    ) {}

    public function handle(): void
    {
        $mission = $this->mission;

        if (!Utils::checkWhatsAppGroupLink(link: $mission->whats_app_link)) {
            return; // Exit if the WhatsApp group link is invalid
        }

        Member::query()
            ->whereIn(
                'id',
                MissionSubscription::query()
                    ->where([
                        'mission_id' => $mission->id,
                        'status' => PRFMissionSubscriptionStatus::APPROVED,
                        'invited_to_group' => false,
                    ])
                    ->select('member_id'),
            )
            ->chunk(30, function ($members) use ($mission) {
                Notification::send($members, new MissionWhatsAppGroupLinkedNotification($mission));

                // Update the invited_to_group status for each member
                foreach ($members as $member) {
                    MissionSubscription::query()
                        ->where('mission_id', $mission->id)
                        ->where('member_id', $member->id)
                        ->get()
                        ->each(fn(MissionSubscription $subscription) => $subscription->update([
                            'invited_to_group' => true,
                            'invited_to_group_at' => now(),
                        ]));
                }
            });

        // Notify the missions desk about the WhatsApp group creation
        Notification::send(
            Utils::deskRecipients(PRFResponsibleDesk::MISSIONS_DESK),
            new MissionWhatsAppGroupLinkedNotification($mission),
        );
    }
}
