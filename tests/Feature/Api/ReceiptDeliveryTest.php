<?php

use App\Enums\PRFDeliveryStatus;
use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFReceiptChannel;
use App\Jobs\LedgerEntry\CreateJob;
use App\Services\Finance\ChartOfAccounts;
use Database\Seeders\TenantReferenceDataSeeder;

beforeEach(function () {
    new TenantReferenceDataSeeder()->run();
});

it('creates a WhatsApp share link for a receipt', function () {
    $chart = app(ChartOfAccounts::class);
    $entry = CreateJob::dispatchSync([
        'financial_account_ulid' => $chart->account(PRFFinancialAccountType::CASH)->ulid,
        'ledger_category_ulid' => $chart->category('income.other')->ulid,
        'amount' => 500,
        'giver_phone' => '+254712345678',
    ]);

    actingAsTenantUser()->postJson(route('api.receipt-deliveries.store'), [
        'ledger_entry_ulid' => $entry->ulid,
        'channel' => PRFReceiptChannel::WHATSAPP->value,
    ])->assertCreated()->assertJsonPath('data.entity', 'receipt-delivery')->assertJsonPath('data.status', PRFDeliveryStatus::LINK_READY->value);
});

it('only sends receipts for receipted income', function () {
    $chart = app(ChartOfAccounts::class);
    $expense = CreateJob::dispatchSync([
        'financial_account_ulid' => $chart->account(PRFFinancialAccountType::CASH)->ulid,
        'ledger_category_ulid' => $chart->category('expense.other')->ulid,
        'amount' => 500,
    ]);

    actingAsTenantUser()->postJson(route('api.receipt-deliveries.store'), [
        'ledger_entry_ulid' => $expense->ulid,
        'channel' => PRFReceiptChannel::EMAIL->value,
        'recipient' => 'someone@example.com',
    ])->assertJsonValidationErrors('ledger_entry_ulid');
});
