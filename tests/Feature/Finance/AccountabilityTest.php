<?php

use App\Enums\PRFReconciliationStatus;
use App\Enums\PRFResponsibleDesk;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\ExpenseCategory;
use App\Models\Refund;
use App\Services\Finance\AccountabilityService;
use Illuminate\Support\Carbon;

/*
 | Chongoria Girls (March 2026) from the treasurer's missions workbook: KES 28,700 disbursed,
 | KES 24,282 really spent (381 of it transaction costs), a KES 30,000 token and a KES 34,418
 | refund leave a zero balance.
 */
function chongoriaMission(): AccountingEvent
{
    $event = AccountingEvent::factory()->create(['name' => 'Chongoria Girls', 'due_date' => '2026-03-06']);
    $fare = ExpenseCategory::factory()->create(['name' => 'Fare']);
    $snacks = ExpenseCategory::factory()->create(['name' => 'Snacks']);
    $airtime = ExpenseCategory::factory()->create(['name' => 'Airtime']);

    AllocationEntry::factory()->for($event)->credit(28_700)->create();
    AllocationEntry::factory()->for($event)->debit(21_140, $fare)->create();
    AllocationEntry::factory()
        ->for($event)
        ->debit(2_561 + 381, $snacks)
        ->create(['charge' => 381]);
    AllocationEntry::factory()->for($event)->debit(200, $airtime)->create();
    AllocationEntry::factory()->for($event)->token(30_000)->create();
    Refund::factory()->for($event)->create(['amount' => 34_418]);

    return $event;
}

it('reproduces the workbook row for a fully accounted mission', function () {
    $event = chongoriaMission();

    $row = app(AccountabilityService::class)->forMonth(Carbon::parse('2026-03-01'))->sole();

    expect($row->event->is($event))
        ->toBeTrue()
        ->and($row->disbursed)
        ->toBe(28_700)
        ->and($row->transactionCosts)
        ->toBe(381)
        ->and($row->totalExpenses())
        ->toBe(24_282)
        ->and($row->tokens)
        ->toBe(30_000)
        ->and($row->toRefund())
        ->toBe(34_418)
        ->and($row->balance())
        ->toBe(0)
        ->and($row->status())
        ->toBe(PRFReconciliationStatus::FULLY_ACCOUNTED);
});

it('flags money that has not been accounted for', function () {
    $event = AccountingEvent::factory()->create(['due_date' => '2026-03-08']);
    AllocationEntry::factory()->for($event)->credit(2_000)->create();

    $row = app(AccountabilityService::class)->forMonth(Carbon::parse('2026-03-01'))->sole();

    expect($row->status())
        ->toBe(PRFReconciliationStatus::NEEDS_ATTENTION)
        ->and($row->remarks())
        ->toBe('Accounting for KES 2,000 pending');
});

it('covers every desk and can be narrowed to one', function () {
    AccountingEvent::factory()->forDesk(PRFResponsibleDesk::PRAYER_DESK)->create(['due_date' => '2026-03-10']);
    AccountingEvent::factory()->forDesk(PRFResponsibleDesk::MISSIONS_DESK)->create(['due_date' => '2026-03-11']);
    AccountingEvent::factory()->create(['due_date' => '2026-04-01']);

    $service = app(AccountabilityService::class);

    expect($service->forMonth(Carbon::parse('2026-03-15')))
        ->toHaveCount(2)
        ->and($service->forMonth(Carbon::parse('2026-03-15'), PRFResponsibleDesk::PRAYER_DESK))
        ->toHaveCount(1);
});

it('lets the treasurer record a verdict with remarks through the API', function () {
    $event = chongoriaMission();

    actingAsTenantUser()->patchJson(route('api.accounting-events.update', $event->ulid), [
        'reconciliation_status' => PRFReconciliationStatus::NEEDS_ATTENTION->value,
    ])->assertJsonValidationErrors('reconciliation_remarks');

    actingAsTenantUser()
        ->patchJson(
            route('api.accounting-events.update', ['ulid' => $event->ulid, 'include' => 'allocationEntries,refunds']),
            [
                'reconciliation_status' => PRFReconciliationStatus::NEEDS_ATTENTION->value,
                'reconciliation_remarks' => 'Receipts for snacks pending',
            ],
        )
        ->assertOk()
        ->assertJsonPath('data.reconciliation_status', PRFReconciliationStatus::NEEDS_ATTENTION->value)
        ->assertJsonPath('data.accountability.balance', 0)
        ->assertJsonPath('data.accountability.remarks', 'Receipts for snacks pending');

    expect($event->fresh()->reconciled_at)->not->toBeNull();
});
