<?php

use App\Contracts\HasTargetApp;
use App\Enums\PRFAppTopics;
use App\Jobs\Mission\NotifyMembersJob;
use App\Models\AppSetting;
use App\Models\Member;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

it('sends notifications when excluded emails setting is missing', function () {
    $user = User::factory()->create();
    $member = Member::factory()->for($user)->create([
        'email' => $user->email,
        'full_name' => $user->name,
    ]);

    AppSetting::query()->where('key', 'organization.excluded_emails')->delete();

    NotificationFacade::fake();

    $notification = new class extends Notification implements HasTargetApp
    {
        public function targetApp(object $notifiable): PRFAppTopics
        {
            return PRFAppTopics::MISSIONS_APP;
        }

        public function via(object $notifiable): array
        {
            return ['mail'];
        }

        public function toMail(object $notifiable): MailMessage
        {
            return (new MailMessage)
                ->subject('Test Notification')
                ->line('This is a test notification.');
        }
    };

    (new NotifyMembersJob($notification))->handle();

    NotificationFacade::assertSentTo($member, $notification::class);
});

it('does not send notifications to excluded emails', function () {
    $includedUser = User::factory()->create();
    $includedMember = Member::factory()->for($includedUser)->create([
        'email' => $includedUser->email,
        'full_name' => $includedUser->name,
    ]);

    $excludedUser = User::factory()->create();
    $excludedMember = Member::factory()->for($excludedUser)->create([
        'email' => $excludedUser->email,
        'full_name' => $excludedUser->name,
    ]);

    AppSetting::set(
        key: 'organization.excluded_emails',
        value: [$excludedUser->email],
        type: 'array',
    );

    NotificationFacade::fake();

    $notification = new class extends Notification implements HasTargetApp
    {
        public function targetApp(object $notifiable): PRFAppTopics
        {
            return PRFAppTopics::MISSIONS_APP;
        }

        public function via(object $notifiable): array
        {
            return ['mail'];
        }

        public function toMail(object $notifiable): MailMessage
        {
            return (new MailMessage)
                ->subject('Test Notification')
                ->line('This is a test notification.');
        }
    };

    (new NotifyMembersJob($notification))->handle();

    NotificationFacade::assertSentTo($includedMember, $notification::class);
    NotificationFacade::assertNotSentTo($excludedMember, $notification::class);
});
