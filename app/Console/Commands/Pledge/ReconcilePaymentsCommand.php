<?php

namespace App\Console\Commands\Pledge;

use App\Enums\PRFPaymentStatus;
use App\Jobs\Pledge\ReconcilePaymentJob;
use App\Models\Payment;
use Illuminate\Console\Command;

class ReconcilePaymentsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pledges:reconcile-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Match successful payments to pledges and record follow-through';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $matched = 0;

        Payment::query()
            ->where('payment_status', PRFPaymentStatus::SUCCESS->value)
            ->whereNull('pledge_id')
            ->chunk(25, function ($payments) {
                foreach ($payments as $payment) {
                    if (ReconcilePaymentJob::dispatchSync($payment)) {
                        $matched++;
                    }
                }
            });

        $this->info("Reconciled {$matched} payment(s) to pledges.");

        return 0;
    }
}
