<?php

namespace App\Console\Commands\Pledge;

use App\Enums\PRFPledgeStatus;
use App\Models\Pledge;
use App\Models\PledgeReminder;
use App\Notifications\Pledge\PledgeDueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class DispatchDueRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pledges:dispatch-due-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for pledges due within the reminder lead window';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $leadDays = (int) config('prf.giving.reminder_lead_days', 7);
        $ccEmail = (string) config('prf.giving.reminder_cc_email', '');
        $today = Carbon::today();
        $window = $today->addDays($leadDays);
        $sent = 0;

        $pledges = Pledge::query()
            ->where('status', PRFPledgeStatus::ACTIVE->value)
            ->whereNotNull('next_due_on')
            ->where('next_due_on', '<=', $window)
            ->get();

        foreach ($pledges as $pledge) {
            // Only reminder for pledges that are still active and have an address.
            if (!filled($pledge->email)) {
                continue;
            }

            // A pledge can still be in the window after a previous reminder if it was
            // never fulfilled, but we should not re-remind the same due date.
            $alreadyReminded = PledgeReminder::query()
                ->where('pledge_id', $pledge->id)
                ->where('due_on', $pledge->next_due_on)
                ->whereNotNull('sent_at')
                ->exists();

            if ($alreadyReminded) {
                continue;
            }

            Notification::send($pledge->email, new PledgeDueNotification($pledge));

            if (filled($ccEmail)) {
                Notification::send($ccEmail, new PledgeDueNotification($pledge));
            }

            PledgeReminder::create([
                'pledge_id' => $pledge->id,
                'due_on' => $pledge->next_due_on,
                'remind_on' => $today,
                'channel' => 'mail',
                'sent_at' => Carbon::now(),
            ]);

            $sent++;
        }

        $this->info("Dispatched {$sent} pledge reminder(s).");

        return 0;
    }
}
