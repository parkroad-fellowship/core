<?php

namespace App\Console\Commands\Pledge;

use App\Console\Concerns\RunsForEachTenant;
use App\Enums\PRFPledgeStatus;
use App\Models\Pledge;
use App\Models\PledgeReminder;
use App\Notifications\Pledge\PledgeDueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class DispatchDueRemindersCommand extends Command
{
    use RunsForEachTenant;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prf:pledges:dispatch-due-reminders';

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
        $sent = 0;

        $this->forEachTenant(function () use (&$sent): void {
            $sent += $this->remindTenantPledges();
        });

        $this->info("Dispatched {$sent} pledge reminder(s).");

        return self::SUCCESS;
    }

    private function remindTenantPledges(): int
    {
        $leadDays = (int) config('prf.app.giving.reminder_lead_days', 7);
        $ccEmail = (string) config('prf.app.giving.reminder_cc_email', '');
        $today = Carbon::today();
        $window = $today->copy()->addDays($leadDays);
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

            Notification::route('mail', $pledge->email)->notify(new PledgeDueNotification($pledge));

            if (filled($ccEmail)) {
                Notification::route('mail', $ccEmail)->notify(new PledgeDueNotification($pledge));
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

        return $sent;
    }
}
