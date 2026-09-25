<?php

use App\Enums\PRFPledgeStatus;
use App\Models\Pledge;
use App\Models\PledgeReminder;
use App\Notifications\Pledge\PledgeDueNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

it('emails pledges that fall due and records the reminder for today', function () {
    Notification::fake();
    Carbon::setTestNow('2026-09-25');

    Pledge::factory()->create([
        'member_id' => null,
        'email' => 'giver@example.org',
        'status' => PRFPledgeStatus::ACTIVE,
        'next_due_on' => '2026-09-28',
    ]);

    $this->artisan('prf:pledges:dispatch-due-reminders')->assertSuccessful();

    Notification::assertSentOnDemand(
        PledgeDueNotification::class,
        fn($notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'giver@example.org',
    );

    expect(PledgeReminder::query()->sole()->remind_on->toDateString())->toBe('2026-09-25');
});
