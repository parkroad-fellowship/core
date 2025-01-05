<?php

namespace App\Jobs\Expense;

use App\Enums\PRFChannelType;
use App\Enums\PRFMorphType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Member;
use App\Models\MpesaRate;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): Expense
    {
        $data = $this->data;

        // Add the transaction charge
        $data['charge'] = match (intval($data['channel_type'])) {
            PRFChannelType::M_PESA->value => MpesaRate::where('transaction_type', ($data['charge_type']))->first()->charge,
        };

        $expenseCategory = ExpenseCategory::query()
            ->where('ulid', $data['expense_category_ulid'])
            ->first();

        $member = Member::query()
            ->where('ulid', $data['member_ulid'])
            ->first();

        $expenseable = PRFMorphType::fromValue($data['expenseable_type'])->getModel()::query()
            ->where('ulid', $data['expenseable_ulid'])
            ->first();

        return Expense::create([
            'expense_category_id' => $expenseCategory->id,
            'member_id' => $member->id,
            'channel_type' => $data['channel_type'],
            'charge_type' => $data['charge_type'],
            'expenseable_id' => $expenseable->id,
            'expenseable_type' => $data['expenseable_type'],
            'amount' => $data['amount'],
            'charge' => $data['charge'],
            'confirmation_message' => $data['confirmation_message'],
        ]);
    }
}
