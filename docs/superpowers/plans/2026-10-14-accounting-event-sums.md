# Accounting Event Totals Without Per-Row Queries Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Serialising an `AccountingEvent` stops running about 12 `SUM` queries per event. Totals are summed in PHP from eager-loaded allocation entries, so a list of events costs a constant number of queries however long it is.

**Architecture:** The seven money attributes (`spent_amount`, `debits`, `amount_received`, `credits`, `balance`, `refund_charge`, `amount_to_refund`) all derive from two numbers: credit total and debit total. A slim `moneyEntries` relation (only `id`, `accounting_event_id`, `entry_type`, `amount`) is eager-loaded with every event through `$with`, and the accessors sum it with collection methods, cached per instance. A separate relation keeps the existing `allocationEntries`-gated `accountability` output unchanged. A small guard caps `limit` on every index.

**Tech Stack:** Laravel 13, PHP 8.4, spatie/laravel-query-builder 7, Pest 5, Postgres 16 (tests via `make test-file`).

**Spec:** Companion to the client plan `Leadership/leadership/docs/superpowers/plans/2026-10-14-lean-data-loading.md`. Server evidence:
- `app/Models/AccountingEvent.php:44-52` appends all 7 money attributes to every serialisation (`#[Appends]`).
- `:176-228`: each accessor runs `allocationEntries()->where(...)->sum('amount')`. `balance` runs 2; `amount_to_refund` runs `balance` twice more through `refund_charge`. That's about 12 queries per event, uncached.
- The client includes `accountingEvent` on missions, requisitions, events and allocation entries, so a 100-row list costs about 1,200 extra queries.
- `app/Http/Resources/AccountingEvent/Resource.php:41` emits `accountability` only when `allocationEntries` **and** `refunds` are loaded, which is why totals must not eager-load `allocationEntries` itself.
- `app/Models/AllocationEntry.php:48,53`: `SoftDeletes`; `entry_type` cast to `PRFEntryType`.
- `app/Http/Controllers/Controller.php:33`: `limit` has no maximum.

## Global Constraints

- **No database summation**: no `->sum()` on a query builder or relation query, no `withSum`, no `selectRaw('SUM(...)')`. Sum in PHP on Eloquent collections only.
- Response shape and values of every money field stay identical (same keys, same integers). `accountability` appears in exactly the same cases as today.
- No migration. `PRFEntryType` values unchanged (`CREDIT = 1`, `DEBIT = 2`).
- Follow `AGENTS.md` / `CLAUDE.md`; format touched files with the repo's formatter.
- Full suite passes: `make test`.

## Review Focus

1. **Totals read after adding an entry to an already-loaded event** must not return the cached value. `refresh()` reloads loaded relations and clears the accessor cache. Task 1 test plus the caller audit in Task 1.
2. **Soft-deleted entries** stay excluded (relation default). Task 1 test.
3. **`accountability` output** must not start appearing just because totals were loaded. Task 2 test.
4. **An event with no entries** returns `0`, not `null`. Task 1 test.
5. **`?limit=1000`** returns at most 200 rows with a next page. Task 3 test.

---

### Task 1: Sum totals in PHP from a slim relation, cached per instance

**Files:**
- Modify: `app/Models/AccountingEvent.php` (add `moneyEntries()` near `allocationEntries()` at `:151`; replace accessors `:172-228`)
- Test: `tests/Feature/Finance/AccountingEventTotalsTest.php` (create)

**Interfaces:**
- Produces: `AccountingEvent::moneyEntries(): HasMany` (columns `id, accounting_event_id, entry_type, amount`); protected `totals(): array{credits:int, debits:int}`.

- [ ] **Step 1: Write the failing test**

```php
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

    return array_map(fn ($q) => strtolower($q['query']), $log);
}

it('computes money fields without any SQL SUM', function () {
    $event = AccountingEvent::factory()->create();
    AllocationEntry::factory()->for($event)->credit(10_000)->create();
    AllocationEntry::factory()->for($event)->debit(3_000)->create();

    $array = [];
    $queries = queriesDuring(function () use ($event, &$array) {
        $array = AccountingEvent::find($event->id)->toArray();
    });

    expect(collect($queries)->filter(fn ($q) => str_contains($q, 'sum('))->count())->toBe(0)
        ->and(count($queries))->toBeLessThanOrEqual(2)
        ->and($array['credits'])->toBe(10_000)
        ->and($array['amount_received'])->toBe(10_000)
        ->and($array['debits'])->toBe(3_000)
        ->and($array['spent_amount'])->toBe(3_000)
        ->and($array['balance'])->toBe(7_000);
});

it('returns zero totals for an event without entries', function () {
    $array = AccountingEvent::factory()->create()->fresh()->toArray();

    expect($array['credits'])->toBe(0)
        ->and($array['debits'])->toBe(0)
        ->and($array['balance'])->toBe(0);
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
```

If `AllocationEntry::factory()->for($event)` does not resolve the relation, use `->state(['accounting_event_id' => $event->id])` (see `database/factories/AllocationEntryFactory.php:23`).

- [ ] **Step 2: Run it to make sure it fails**

Run: `make test-file FILE=tests/Feature/Finance/AccountingEventTotalsTest.php`
Expected: "computes money fields without any SQL SUM" FAILS (about 12 `sum(` queries). The others may pass.

- [ ] **Step 3: Implement**

Add next to `allocationEntries()` (`:151`):

```php
    /**
     * Slim entries used only to total money fields in PHP. Kept separate from
     * allocationEntries() so loading it does not trigger the accountability
     * block in the API resource.
     */
    public function moneyEntries(): HasMany
    {
        return $this->hasMany(AllocationEntry::class)
            ->select(['id', 'accounting_event_id', 'entry_type', 'amount']);
    }
```

Check the foreign key matches `allocationEntries()` (if that relation passes an explicit key, pass the same one).

Replace `:172-228` (from `spentAmount()` through `calculateAmountToRefund()`) with:

```php
    /**
     * Credit and debit totals, summed in PHP from moneyEntries. Lazy-loads the
     * relation once when it was not eager-loaded. Accessors below cache per
     * instance; refresh() reloads relations and clears that cache.
     *
     * @return array{credits: int, debits: int}
     */
    protected function totals(): array
    {
        $entries = $this->moneyEntries;

        return [
            'credits' => (int) $entries->where('entry_type', PRFEntryType::CREDIT)->sum('amount'),
            'debits' => (int) $entries->where('entry_type', PRFEntryType::DEBIT)->sum('amount'),
        ];
    }

    protected function credits(): Attribute
    {
        return Attribute::make(get: fn () => $this->totals()['credits'])->shouldCache();
    }

    protected function debits(): Attribute
    {
        return Attribute::make(get: fn () => $this->totals()['debits'])->shouldCache();
    }

    protected function spentAmount(): Attribute
    {
        return Attribute::make(get: fn () => (int) $this->debits);
    }

    protected function amountReceived(): Attribute
    {
        return Attribute::make(get: fn () => (int) $this->credits);
    }

    protected function balance(): Attribute
    {
        return Attribute::make(get: fn () => (int) ($this->credits - $this->debits))->shouldCache();
    }

    protected function refundCharge(): Attribute
    {
        return Attribute::make(
            get: fn () => (int) Utils::getCharge(
                chargeType: PRFTransactionType::MPESA_PAYBILL_BUSINESS_TARRIFF,
                amount: $this->balance,
            ),
        )->shouldCache();
    }

    protected function amountToRefund(): Attribute
    {
        return Attribute::make(get: fn () => (int) ($this->balance - $this->refund_charge));
    }
```

`$entries->sum('amount')` is `Collection::sum`, which runs in PHP. `where('entry_type', PRFEntryType::CREDIT)` compares the cast enum instances.

Audit callers:
- `rg -n "calculateBalance|calculateRefundCharge|calculateAmountToRefund" app tests`: replace hits with the attribute (`->balance`, `->refund_charge`, `->amount_to_refund`).
- `rg -n -A12 "AllocationEntry::create|allocationEntries\(\)->create|Refund::create" app`: where a total is read on the **same** in-memory event after the write, add `$accountingEvent->refresh();` before the read.

- [ ] **Step 4: Run the tests**

Run: `make test-file FILE=tests/Feature/Finance/AccountingEventTotalsTest.php` → PASS (4 tests).
Run: `make test-filter FILTER="Ledger|Accountability|Refund|AllocationEntry|Requisition"` → PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/AccountingEvent.php tests/Feature/Finance/AccountingEventTotalsTest.php
git commit -m "perf: sum accounting event totals in PHP from a slim relation"
```

---

### Task 2: Eager-load the slim entries with every event

**Files:**
- Modify: `app/Models/AccountingEvent.php` (add `$with`)
- Test: `tests/Feature/Finance/AccountingEventTotalsTest.php` (extend)

**Interfaces:**
- Consumes: `moneyEntries()` (Task 1).

- [ ] **Step 1: Write the failing tests**

Append:

```php
it('does not add queries per event when listing events', function () {
    $seed = function (int $count) {
        AccountingEvent::query()->withoutGlobalScopes()->forceDelete();
        foreach (AccountingEvent::factory()->count($count)->create() as $event) {
            AllocationEntry::factory()->for($event)->credit(1_000)->create();
            AllocationEntry::factory()->for($event)->debit(400)->create();
        }
    };

    $seed(1);
    $forOne = count(queriesDuring(fn () => AccountingEvent::query()->get()->toArray()));

    $seed(10);
    $arrays = [];
    $forTen = count(queriesDuring(function () use (&$arrays) {
        $arrays = AccountingEvent::query()->get()->toArray();
    }));

    expect($forTen)->toBe($forOne)
        ->and(collect($arrays)->pluck('balance')->unique()->values()->all())->toBe([600]);
});

it('does not emit accountability unless allocationEntries and refunds are loaded', function () {
    $event = AccountingEvent::factory()->create();
    AllocationEntry::factory()->for($event)->credit(1_000)->create();

    $resource = (new \App\Http\Resources\AccountingEvent\Resource(AccountingEvent::find($event->id)))
        ->toArray(request());

    expect($resource['accountability'] ?? null)->toBeInstanceOf(\Illuminate\Http\Resources\MissingValue::class);
});
```

If the resource's `when(...)` for `accountability` resolves differently (`rg -n "accountability" app/Http/Resources/AccountingEvent/Resource.php`), assert on `json_decode(json_encode($resource->response()->getData()), true)` not having the `accountability` key instead.

- [ ] **Step 2: Run them to make sure the first fails**

Run: `make test-file FILE=tests/Feature/Finance/AccountingEventTotalsTest.php`
Expected: "does not add queries per event…" FAILS (each event lazy-loads `moneyEntries`).

- [ ] **Step 3: Implement**

In `AccountingEvent`, add:

```php
    /** Always load the slim entries so money fields need no per-row queries. */
    protected $with = ['moneyEntries'];
```

`$with` also applies when events are eager-loaded as an include (`?include=accountingEvent` on missions, requisitions, events and allocation entries), so those lists stay constant-query too.

- [ ] **Step 4: Run tests and commit**

Run: `make test-file FILE=tests/Feature/Finance/AccountingEventTotalsTest.php` → PASS (6 tests). Run: `make test` → PASS.

```bash
git add app/Models/AccountingEvent.php tests/Feature/Finance/AccountingEventTotalsTest.php
git commit -m "perf: eager-load slim entries with accounting events"
```

---

### Task 3: Cap `limit` on index endpoints

**Files:**
- Modify: `app/Http/Controllers/Controller.php:18-34`
- Test: `tests/Feature/Api/IndexLimitTest.php` (create)

**Interfaces:**
- Produces: `protected int $maxLimit = 200;` on the base controller.

The Leadership client asks for `limit=500` on class groups (`lib/features/missions/cubit/class_group_resource_cubit.dart:23,36`). With the cap it gets 200 per page, and its page crawl fetches the rest.

- [ ] **Step 1: Write the failing test**

Copy the authenticated-user and tenant `beforeEach` from `tests/Feature/Api/DepartmentTest.php`:

```php
<?php

use App\Models\Department;

// beforeEach: same auth + tenant setup as DepartmentTest.php

it('caps limit at 200 and keeps a next page', function () {
    Department::factory()->count(205)->create();

    $response = $this->getJson('/api/v1/departments?limit=1000');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(200)
        ->and($response->json('links.next'))->not->toBeNull();
});
```

- [ ] **Step 2: Run it to make sure it fails**

Run: `make test-file FILE=tests/Feature/Api/IndexLimitTest.php`
Expected: FAIL (205 rows).

- [ ] **Step 3: Implement**

In `Controller.php` add `protected int $maxLimit = 200;` beside `$defaultLimit`, and in `index()`:

```php
            ->simplePaginate(min(request()->integer('limit', $this->defaultLimit), $this->maxLimit));
```

Apply the same `min(...)` to any controller that paginates itself: `rg -n "simplePaginate|->paginate\(" app/Http/Controllers`.

- [ ] **Step 4: Run tests and commit**

Run: `make test-file FILE=tests/Feature/Api/IndexLimitTest.php` → PASS, then `make test` → PASS.

```bash
git add app/Http/Controllers tests/Feature/Api/IndexLimitTest.php
git commit -m "perf: cap index limit at 200"
```

---

## Not in this plan (considered, deferred)

- **Removing `Mission::$withCount`**: rejected. It feeds `mission_subscriptions_needed`, which `Resources/Mission/Resource.php:32` outputs; removing it adds 2 count queries per mission.
- **`updated_since` delta filter**: revisit after the client's freshness gate is measured. True delta sync also needs a deleted-records feed.
- **ETag / 304**: media resources sign new URLs on every call, so list bodies never match.
- **`allowedFields`**: the client gets most of the payload saving by dropping includes.
