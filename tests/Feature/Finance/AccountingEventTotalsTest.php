<?php

use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use Illuminate\Support\Facades\DB;

function queriesDuring(callable $fn): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $fn();
    $log = DB::getQueryLog();
    DB::disableQueryLog();

    return array_map(fn($q) => strtolower($q['query']), $log);
}

it('computes money fields without any SQL SUM', function () {
    $event = AccountingEvent::factory()->create();
    AllocationEntry::factory()->for($event)->credit(10_000)->create();
    AllocationEntry::factory()->for($event)->debit(3_000)->create();

    $array = [];
    $queries = queriesDuring(function () use ($event, &$array) {
        $array = AccountingEvent::find($event->id)->toArray();
    });

    // event + moneyEntries + the transfer_rates lookup behind refund_charge
    expect(collect($queries)->filter(fn($q) => str_contains($q, 'sum('))->count())
        ->toBe(0)
        ->and(count($queries))
        ->toBeLessThanOrEqual(3)
        ->and($array['credits'])
        ->toBe(10_000)
        ->and($array['amount_received'])
        ->toBe(10_000)
        ->and($array['debits'])
        ->toBe(3_000)
        ->and($array['spent_amount'])
        ->toBe(3_000)
        ->and($array['balance'])
        ->toBe(7_000);
});

it('returns zero totals for an event without entries', function () {
    $array = AccountingEvent::factory()->create()->fresh()->toArray();

    expect($array['credits'])->toBe(0)->and($array['debits'])->toBe(0)->and($array['balance'])->toBe(0);
});

it('excludes soft-deleted entries', function () {
    $event = AccountingEvent::factory()->create();
    AllocationEntry::factory()->for($event)->credit(1_000)->create();
    AllocationEntry::factory()->for($event)->credit(9_000)->create()->delete();

    expect(AccountingEvent::find($event->id)->credits)->toBe(1_000);
});

it('reflects a new entry after refresh()', function () {
    $event = AccountingEvent::factory()->create();
    AllocationEntry::factory()->for($event)->credit(5_000)->create();
    $event = AccountingEvent::find($event->id);
    expect($event->balance)->toBe(5_000);

    AllocationEntry::factory()->for($event)->debit(2_000)->create();
    $event->refresh();

    expect($event->balance)->toBe(3_000);
});

it('does not add queries per event when listing events', function () {
    $seed = function (int $count) {
        AllocationEntry::query()->withoutGlobalScopes()->forceDelete();
        AccountingEvent::query()->withoutGlobalScopes()->forceDelete();
        foreach (AccountingEvent::factory()->count($count)->create() as $event) {
            AllocationEntry::factory()->for($event)->credit(1_000)->create();
            AllocationEntry::factory()->for($event)->debit(400)->create();
        }
    };

    $seed(1);
    AccountingEvent::query()->get()->toArray(); // warm the per-request transfer-rate memo
    $forOne = count(queriesDuring(fn() => AccountingEvent::query()->get()->toArray()));

    $seed(10);
    $arrays = [];
    $forTen = count(queriesDuring(function () use (&$arrays) {
        $arrays = AccountingEvent::query()->get()->toArray();
    }));

    expect($forTen)->toBe($forOne)->and(collect($arrays)->pluck('balance')->unique()->values()->all())->toBe([600]);
});

it('keeps moneyEntries out of the serialised event', function () {
    $event = AccountingEvent::factory()->create();
    AllocationEntry::factory()->for($event)->credit(1_000)->create();

    expect(AccountingEvent::find($event->id)->toArray())->not->toHaveKey('money_entries');
});

it('does not emit accountability unless allocationEntries and refunds are loaded', function () {
    $event = AccountingEvent::factory()->create();
    AllocationEntry::factory()->for($event)->credit(1_000)->create();

    $resource = new \App\Http\Resources\AccountingEvent\Resource(AccountingEvent::find($event->id))->toArray(request());

    expect($resource['accountability'] ?? null)->toBeInstanceOf(\Illuminate\Http\Resources\MissingValue::class);
});

it('sees an edited transfer rate in the next queued job', function () {
    $rate = \App\Models\TransferRate::query()->where('charge', '>', 0)->orderBy('id')->firstOrFail();
    $type = $rate->transaction_type;
    $amount = (int) $rate->min_amount;

    $before = \App\Helpers\Utils::getCharge($type, $amount);
    $rate->update(['charge' => $before + 50]);

    // Same process, no job boundary: the memo is still served.
    expect(\App\Helpers\Utils::getCharge($type, $amount))->toBe($before);

    $job = \Mockery::mock(\Illuminate\Contracts\Queue\Job::class);
    $job->shouldReceive('payload')->andReturn(['tenant_id' => tenant('id')]);
    event(new \Illuminate\Queue\Events\JobProcessing('sync', $job));

    expect(\App\Helpers\Utils::getCharge($type, $amount))->toBe($before + 50);
});

it('derives refund_charge and amount_to_refund from the transfer rate band of the balance', function () {
    $band = \App\Models\TransferRate::query()
        ->where('transaction_type', \App\Enums\PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF->value)
        ->where('charge', '>', 0)
        ->orderBy('id')
        ->firstOrFail();
    $balance = (int) $band->min_amount;

    $event = AccountingEvent::factory()->create();
    AllocationEntry::factory()
        ->for($event)
        ->credit($balance + 1_000)
        ->create();
    AllocationEntry::factory()->for($event)->debit(1_000)->create();

    $array = AccountingEvent::find($event->id)->toArray();

    expect($array['balance'])
        ->toBe($balance)
        ->and($array['refund_charge'])
        ->toBe((int) $band->charge)
        ->and($array['amount_to_refund'])
        ->toBe($balance - (int) $band->charge);
});

it('computes refund deficits from live (non-deleted) refunds only', function () {
    $event = AccountingEvent::factory()->create();
    AllocationEntry::factory()->for($event)->credit(10_000)->create();
    \App\Models\Refund::factory()->for($event)->create(['amount' => 1_000, 'charge' => 5]);
    \App\Models\Refund::factory()->for($event)->create(['amount' => 9_000, 'charge' => 99])->delete();

    $event = AccountingEvent::find($event->id);
    $newCharge = \App\Helpers\Utils::getCharge(\App\Enums\PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF, 2_000);

    $refund = \App\Jobs\Refund\CreateJob::dispatchSync([
        'accounting_event_ulid' => $event->ulid,
        'amount' => 2_000,
        'confirmation_message' => 'QWE125 Confirmed',
    ]);

    $expected = $event->amount_to_refund - 3_000 + max(0, 5 + $newCharge - $event->refund_charge);
    expect($refund->deficit_amount)->toBe($expected);

    // The observer recalculates the latest refund when the balance changes.
    AllocationEntry::factory()->for($event)->debit(500)->create();
    $event = AccountingEvent::find($event->id);
    $latest = $event->latestRefund;

    $expected = $event->amount_to_refund - 3_000 + max(0, 5 + $newCharge - $event->refund_charge);
    expect($latest->deficit_amount)->toBe($expected);
});
