<?php

namespace App\Notifications\Requisition;

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Enums\PRFEnvironment;
use App\Models\Requisition;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class RecallNotification extends Notification implements HasTargetApp, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Requisition $requisition
    ) {
        //
    }

    public function targetApp(object $notifiable): PRFAppTopics
    {
        return PRFAppTopics::LEADERSHIP_APP;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];
        if (! empty($notifiable->fcm_tokens)) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $requisition = $this->requisition;
        $requisition->load(['accountingEvent', 'member']);

        $eventName = $requisition->accountingEvent->name ?? 'N/A';
        $requesterName = $requisition->member->full_name ?? 'N/A';
        $totalAmount = number_format($requisition->total_amount, 2);

        return (new MailMessage)
            ->subject("⚠️ Requisition Recalled - {$eventName}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line('**IMPORTANT:** This requisition has been **recalled** and is **NO LONGER VALID**.')
            ->line('')
            ->line('**Please do not take any further action on this requisition.**')
            ->line('')
            ->line('**Requisition Details:**')
            ->line("• **Event:** {$eventName}")
            ->line("• **Requested by:** {$requesterName}")
            ->line("• **Total Amount:** KES {$totalAmount}")
            ->line('• **Recall Date:** '.now()->format('d/m/Y H:i:s'))
            ->line('')
            ->line('**Important Information:**')
            ->line('• All prior approvals have been cancelled')
            ->line('• Any pending payment instructions have been cancelled')
            ->line('• No payments should be processed for this requisition')
            ->line('• A new requisition may be submitted if needed')
            ->line('')
            ->action('View Requisition Details', config('prf.app.leadership_app.android.url'))
            ->line('')
            ->line('If you have any questions about this recall, please contact the requisition originator.')
            ->line('')
            ->line('Thank you for your attention to this matter.')
            ->salutation('Best regards,');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }

    public function toFcm($notifiable)
    {
        $requisition = $this->requisition;
        $requisition->load(['accountingEvent']);

        $eventName = $requisition->accountingEvent->name ?? 'Unknown Event';
        $totalAmount = number_format($requisition->total_amount, 2);

        $title = '⚠️ Requisition Recalled';
        $body = "The {$eventName} requisition (KES {$totalAmount}) has been recalled. No action is needed.";

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body
        )))
            ->data([
                'type' => 'requisition_recalled',
                'requisition_ulid' => $requisition->ulid,
                'event_name' => $eventName,
                'total_amount' => (string) $requisition->total_amount,
                'notification_action' => 'view_requisition',
                'target_app' => PRFAppTopics::LEADERSHIP_APP->value,
            ])->topic(
                PRFEnvironment::fromEnv(config('app.env'))->value
                .'_'
                .PRFAppTopics::LEADERSHIP_APP->value
            );
    }
}
