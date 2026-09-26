<?php

namespace App\Console\Commands\Pledge;

use App\Console\Concerns\RunsForEachTenant;
use App\Enums\PRFPaymentStatus;
use App\Jobs\Pledge\ReconcilePaymentJob;
use App\Models\Payment;
use Illuminate\Console\Command;

class ReconcilePaymentsCommand extends Command
{
    use RunsForEachTenant;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prf:pledges:reconcile-payments';

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

        $this->forEachTenant(function () use (&$matched): void {
            Payment::query()
                ->where('payment_status', PRFPaymentStatus::SUCCESS->value)
                ->whereNull('pledge_id')
                ->lazyById(25)
                ->each(function (Payment $payment) use (&$matched): void {
                    if (ReconcilePaymentJob::dispatchSync($payment)) {
                        $matched++;
                    }
                });
        });

        $this->info("Reconciled {$matched} payment(s) to pledges.");

        return self::SUCCESS;
    }
}
