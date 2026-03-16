<?php

namespace App\Notifications\StudentEnquiry;

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Enums\PRFEnvironment;
use App\Models\StudentEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewStudentEnquiryNotification extends Notification implements HasTargetApp, ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public StudentEnquiry $studentEnquiry,
    ) {
        //
    }

    public function targetApp(object $notifiable): PRFAppTopics
    {
        return PRFAppTopics::MISSIONS_APP;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [];
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
        $studentEnquiry = $this->studentEnquiry;
        $appStores = config('prf.app.app_stores');

        return (new MailMessage)
            ->replyTo(config('prf.app.missions_desk.emails')[0] ?? config('mail.from.address'))
            ->subject('📩 New Student Enquiry Requires Your Response')
            ->greeting("Hello {$notifiable->full_name},")
            ->line('📱 **Student Enquiry Alert**')
            ->line('')
            ->line('A student has submitted an enquiry and needs your assistance. Please respond as soon as possible.')
            ->line('')
            ->line('**📝 Student Enquiry:**')
            ->line("_{$studentEnquiry->content}_")
            ->line('')
            ->line('🎯 **How to Respond:**')
            ->line('1. Open the PRF missions app on your device')
            ->line('2. Navigate to the "Minister to a student" section')
            ->line('3. View the full enquiry details and respond appropriately')
            ->line('')
            ->line('📚 **Need Help Answering?**')
            ->line('If you\'re unsure how to respond to this enquiry, please review these helpful resources:')
            ->line('• [Resource Preparation Guide](http://bit.ly/4iceBUU)')
            ->line('• [Mission Guidelines & Expectations](http://bit.ly/43yfEtP)')
            ->line('')
            ->line('❓ **Still Need Assistance?**')
            ->line('Contact the mission desk if you need guidance on how to respond to this enquiry.')
            ->line('')
            ->line('**📱 Access the App:**')
            ->line('')
            ->action('📱 Open Android App', $appStores['android']['url'])
            ->line('**Alternative Downloads:**')
            ->line("🍎 [iOS App Store]({$appStores['ios']['url']})")
            ->line("📲 [Huawei AppGallery]({$appStores['huawei']['url']})")
            ->line('')
            ->line('Thank you for your commitment to supporting our students! 🙏');
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm($notifiable)
    {
        $studentEnquiry = $this->studentEnquiry;
        $title = 'New Student Enquiry';
        $body = 'A student has submitted an enquiry and needs your response.';

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body
        )))
            ->data([
                'type' => 'student_enquiry',
                'student_enquiry_ulid' => $studentEnquiry->ulid,
                'target_app' => PRFAppTopics::MISSIONS_APP->value,
            ])->topic(
                PRFEnvironment::fromEnv(config('app.env'))->value
                .'_'
                .PRFAppTopics::MISSIONS_APP->value
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
