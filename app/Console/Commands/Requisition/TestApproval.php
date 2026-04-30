<?php

namespace App\Console\Commands\Requisition;

use App\Jobs\Requisition\ApproveJob;
use App\Jobs\Requisition\RejectJob;
use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Console\Command;

class TestApproval extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-approval';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $r = Requisition::query()
            ->has('paymentInstruction')
            ->has('requisitionItems')
            ->firstOrFail();

        ApproveJob::dispatchSync(
            $r->ulid,
            [
                'approved_by_ulid' => Member::query()->firstOrFail()->ulid,
            ]
        );

        RejectJob::dispatchSync(
            $r->ulid,
            [
                'approved_by_ulid' => Member::query()->firstOrFail()->ulid,
                'approval_notes' => 'Cool beans',
            ]
        );
    }
}
