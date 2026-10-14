<?php

use App\Enums\PRFFinancialAccountType;
use App\Enums\PRFLedgerChannel;
use App\Enums\PRFLedgerFlow;
use App\Models\LedgerCategory;
use App\Models\LedgerEntry;
use App\Services\Finance\ChartOfAccounts;
use Database\Seeders\TenantReferenceDataSeeder;

beforeEach(function () {
    new TenantReferenceDataSeeder()->run();
    $this->chart = app(ChartOfAccounts::class);
});

describe('index', function () {
    it('filters the cashbook by account and date', function () {
        $paybill = $this->chart->account(PRFFinancialAccountType::PAYBILL);
        LedgerEntry::factory()->for($paybill)->create(['transacted_on' => '2026-03-02']);
        LedgerEntry::factory()->for($paybill)->create(['transacted_on' => '2026-01-02']);
        LedgerEntry::factory()->create(['transacted_on' => '2026-03-02']);

        actingAsTenantUser()
            ->getJson(route('api.ledger-entries.index', [
                'filter' => ['financial_account_ulid' => $paybill->ulid, 'from' => '2026-03-01'],
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });
});

describe('store', function () {
    it('receipts offline income with a receipt number and link', function () {
        actingAsTenantUser()
            ->postJson(route('api.ledger-entries.store'), [
                'financial_account_ulid' => $this->chart->account(PRFFinancialAccountType::BANK)->ulid,
                'ledger_category_ulid' => $this->chart->category('income.appreciation_from_schools')->ulid,
                'channel' => PRFLedgerChannel::BANK_DEPOSIT->value,
                'amount' => 30_000,
                'transacted_on' => now()->toDateString(),
                'counterparty' => 'Chongoria Girls',
                'giver_phone' => '0712 345 678',
            ])
            ->assertCreated()
            ->assertJsonPath('data.entity', 'ledger-entry')
            ->assertJsonPath('data.flow', PRFLedgerFlow::RECEIPT->value)
            ->assertJsonPath('data.receipt_number', 'PRF-' . now()->format('Y') . '-000001')
            ->assertJsonPath('data.is_auto_posted', false);
    });

    it('validates input', function (array $payload, string $field) {
        actingAsTenantUser()->postJson(route('api.ledger-entries.store'), $payload)->assertJsonValidationErrors($field);
    })->with([
        'missing amount' => [[], 'amount'],
        'zero amount' => [['amount' => 0], 'amount'],
        'future date' => [['transacted_on' => now()->addDay()->toDateString()], 'transacted_on'],
        'bad phone' => [['giver_phone' => '12'], 'giver_phone'],
    ]);
});

describe('destroy', function () {
    it('soft deletes a line', function () {
        $entry = LedgerEntry::factory()->create();

        actingAsTenantUser()->deleteJson(route('api.ledger-entries.destroy', $entry->ulid))->assertNoContent();

        $this->assertSoftDeleted($entry);
    });
});

it('never lets a coded category be deleted', function () {
    $category = $this->chart->transactionCosts();

    actingAsTenantUser()->deleteJson(route('api.ledger-categories.destroy', $category->ulid))->assertForbidden();

    expect(LedgerCategory::query()->whereKey($category->id)->exists())->toBeTrue();
});
