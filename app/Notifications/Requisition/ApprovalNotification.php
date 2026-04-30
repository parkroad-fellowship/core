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
use Illuminate\Support\Facades\Storage;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class ApprovalNotification extends Notification implements HasTargetApp, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Requisition $requisition,
        public string $fileName,
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
        $requisition->load(['accountingEvent', 'member', 'approvedBy']);

        $eventName = $requisition->accountingEvent->name ?? 'N/A';
        $requesterName = $requisition->member->full_name ?? 'N/A';
        $approverName = $requisition->approvedBy->full_name ?? 'System';
        $totalAmount = number_format($requisition->total_amount, 2);

        return (new MailMessage)
            ->subject("✅ Requisition Approved - {$eventName}")
            ->greeting("Hello {$notifiable->full_name},")
            ->line('Great news! This requisition has been **approved** and is ready for processing.')
            ->line('')
            ->line('**Requisition Details:**')
            ->line("• **Event:** {$eventName}")
            ->line("• **Requested by:** {$requesterName}")
            ->line("• **Total Amount:** KES {$totalAmount}")
            ->line("• **Approved by:** {$approverName}")
            ->line('• **Approval Date:** '.now()->format('d/m/Y H:i:s'))
            ->line('')
            ->line('**Next Steps:**')
            ->line('1. The attached Excel report contains all requisition details for accounting purposes')
            ->line('2. Payment instruction will be processed according to the provided details')
            ->line('')
            ->action('View Full Requisition', config('prf.app.leadership_app.android.url'))
            ->line('')
            ->line('The detailed requisition report is attached to this email for your records and accounting purposes.')
            ->line('')
            ->salutation('Best regards,')
            ->line('---')
            ->attachData(Storage::get($this->fileName), $this->fileName, [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
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
        $requisition->load(['accountingEvent', 'member']);

        $eventName = $requisition->accountingEvent->name ?? 'Unknown Event';
        $totalAmount = number_format($requisition->total_amount, 2);

        $title = '✅ Requisition Approved';
        $body = "The {$eventName} requisition (KES {$totalAmount}) has been approved and is being processed.";

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body
        )))
            ->data([
                'type' => 'requisition_approved',
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
