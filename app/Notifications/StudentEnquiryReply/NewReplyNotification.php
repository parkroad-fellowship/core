<?php

namespace App\Notifications\StudentEnquiryReply;

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Enums\PRFEnvironment;
use App\Models\StudentEnquiryReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewReplyNotification extends Notification implements HasTargetApp, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public StudentEnquiryReply $studentEnquiryReply,
    ) {
        //
    }

    public function targetApp(object $notifiable): PRFAppTopics
    {
        return $notifiable instanceof \App\Models\Member
            ? PRFAppTopics::MISSIONS_APP
            : PRFAppTopics::STUDENTS_APP;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [
            // 'mail'
        ];
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
        $reply = $this->studentEnquiryReply;
        $appStores = config('prf.app.app_stores');

        return (new MailMessage)
            ->replyTo(config('prf.app.missions_desk.emails')[0] ?? config('mail.from.address'))
            ->subject('📝 Your Student Enquiry Has a New Reply')
            ->greeting("Hello {$notifiable->full_name},")
            ->line('📬 **Student Enquiry Reply Alert**')
            ->line('')
            ->line('Your student enquiry has received a new reply. Please review the response and continue the conversation if needed.')
            ->line('')
            ->line('**💬 Reply:**')
            ->line("_{$reply->content}_")
            ->line('')
            ->line('🎯 **How to View the Reply:**')
            ->line('1. Open the PRF missions app on your device')
            ->line('2. Navigate to your student enquiries')
            ->line('3. View the full reply and respond if necessary')
            ->line('')
            ->line('**📱 Access the App:**')
            ->action('📱 Open Android App', $appStores['android']['url'])
            ->line("🍎 [iOS App Store]({$appStores['ios']['url']})")
            ->line("📲 [Huawei AppGallery]({$appStores['huawei']['url']})")
            ->line('')
            ->line('Thank you for staying engaged! 🙏');
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm($notifiable)
    {
        $reply = $this->studentEnquiryReply;
        $reply->load(['studentEnquiry', 'commentorable']);
        if (! $reply->studentEnquiry) {
            return;
        }

        $title = $notifiable->full_name ?? $notifiable->name;

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $reply->content
        )))
            ->data([
                'type' => 'student_enquiry_reply',
                'student_enquiry_ulid' => $reply->studentEnquiry->ulid,
                'target_app' => $notifiable->full_name !== null ? PRFAppTopics::MISSIONS_APP->value : PRFAppTopics::STUDENTS_APP->value,
            ])->topic(
                PRFEnvironment::fromEnv(config('app.env'))->value
                .'_'
                .($notifiable->full_name !== null ? PRFAppTopics::MISSIONS_APP->value : PRFAppTopics::STUDENTS_APP->value)
            );
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
}
