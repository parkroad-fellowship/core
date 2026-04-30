<?php

namespace App\Console\Commands\Payment;

use App\Enums\PRFPaymentStatus;
use App\Jobs\Payment\CheckStatusJob;
use App\Models\Payment;
use Illuminate\Console\Command;

class CheckStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the status of the payment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Payment::query()
            ->where('payment_status', PRFPaymentStatus::INITIALISED)
            ->chunk(10, function ($payments) {
                foreach ($payments as $payment) {
                    CheckStatusJob::dispatch($payment);
                }
            });
    }
}
