<?php

use App\Enums\PRFFinancialAccountType;
use App\Services\Finance\ChartOfAccounts;
use Database\Seeders\TenantReferenceDataSeeder;

beforeEach(function () {
    new TenantReferenceDataSeeder()->run();
    $this->chart = app(ChartOfAccounts::class);
});

it('transfers between accounts', function () {
    $paybill = $this->chart->account(PRFFinancialAccountType::PAYBILL);
    $bank = $this->chart->account(PRFFinancialAccountType::BANK);

    actingAsTenantUser()
        ->postJson(route('api.account-transfers.store', ['include' => 'fromAccount,toAccount,ledgerEntries']), [
            'from_financial_account_ulid' => $paybill->ulid,
            'to_financial_account_ulid' => $bank->ulid,
            'amount' => 75_000,
            'charge' => 83,
            'transferred_on' => now()->toDateString(),
            'reference' => 'TA45ZSLTTX',
        ])
        ->assertCreated()
        ->assertJsonPath('data.entity', 'account-transfer')
        ->assertJsonCount(3, 'data.ledger_entries')
        ->assertJsonPath('data.to_account.balance', 75_000);
});

it('updates a transfer and its cashbook lines together', function () {
    $paybill = $this->chart->account(PRFFinancialAccountType::PAYBILL);
    $bank = $this->chart->account(PRFFinancialAccountType::BANK);

    $ulid = actingAsTenantUser()->postJson(route('api.account-transfers.store'), [
        'from_financial_account_ulid' => $paybill->ulid,
        'to_financial_account_ulid' => $bank->ulid,
        'amount' => 1_000,
        'transferred_on' => now()->toDateString(),
    ])->json('data.ulid');

    actingAsTenantUser()->patchJson(route('api.account-transfers.update', $ulid), ['amount' => 2_000])->assertOk();

    expect($bank->fresh()->balance)->toBe(2_000)->and($paybill->fresh()->balance)->toBe(-2_000);
});

it('refuses a transfer to the same account', function () {
    $paybill = $this->chart->account(PRFFinancialAccountType::PAYBILL);

    actingAsTenantUser()->postJson(route('api.account-transfers.store'), [
        'from_financial_account_ulid' => $paybill->ulid,
        'to_financial_account_ulid' => $paybill->ulid,
        'amount' => 10,
        'transferred_on' => now()->toDateString(),
    ])->assertJsonValidationErrors('to_financial_account_ulid');
});
