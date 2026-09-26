# Jobs and side effects

## Where things belong

| Concern | Lives in |
|---|---|
| Any write to the database | a **Job** (`app/Jobs/{Domain}/`) |
| Follow-up work after a write (notify, schedule work, broadcast) | a **domain event**, handled by **queued listeners** |
| Keeping a model internally consistent (derived columns, cache, slug) | an **Observer** |
| Talking to an external provider | a **Service** behind an interface, called from a queued job or listener |

API controllers, Filament actions, commands and imports all call the **same jobs**, so every entry point triggers the same side effects.

## Hard rule: no query-level writes

Jobs, listeners and actions never call `update()`, `delete()`, `forceDelete()`, `restore()`, `increment()` or `decrement()` on an Eloquent **Builder** or **Relation**. Those calls skip observers, domain events and the activity log. A custom PHPStan rule (`NoQueryBuilderWritesRule`) enforces this.

```php
// ✗ Never
Mission::query()->where('ulid', $ulid)->update($data);

// ✓ Load the model, then write through it
$mission = Mission::query()->where('ulid', $ulid)->firstOrFail();
$mission->update($data);

// ✓ Sets of rows
MissionSubscription::query()->where(...)->lazyById()->each(
    fn (MissionSubscription $subscription) => $subscription->update(['status' => PRFMissionSubscriptionStatus::CONFLICT]),
);
```

The **only** way to skip side effects is `updateQuietly()` or `saveQuietly()` on a model, with a one-line PHPDoc on the job saying why.

## The four job shapes

Copy the template that matches. Don't write "Create a new job instance." or "Execute the job." docblocks.

### 1. CRUD job (sync): `CreateJob` / `UpdateJob`

```php
class UpdateJob
{
    use Dispatchable;
    use ResolvesULIDs;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): Mission
    {
        $mission = Mission::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->resolveULIDs($this->data, [
            'school_ulid' => School::class,
            'mission_type_ulid' => MissionType::class,
        ]);

        $mission->update($attributes); // the observer turns meaningful changes into domain events

        return $mission;
    }
}
```

- The signature is always `CreateJob(array $data): Model` or `UpdateJob(array $data, string $ulid): Model`, with the data first and the parameter always named `$ulid`.
- Convert `*_ulid` keys with `ResolvesULIDs::resolveULIDs()`. Look records up with `firstOrFail()`, never `->first()->id`.
- Wrap work that touches several tables in `DB::transaction()`.
- A status change arriving through `UpdateJob` is handed to the matching action job.

### 2. Action job (sync): a state transition

```php
class ApproveJob
{
    use Dispatchable;

    public function __construct(
        public Requisition $requisition,
        public User $actor,
        public array $data = [],
    ) {}

    public function handle(): Requisition
    {
        return DB::transaction(function (): Requisition {
            throw_unless($this->requisition->approval_status->canTransitionTo(PRFApprovalStatus::APPROVED), InvalidStateTransition::class);

            $this->requisition->update([...]);

            RequisitionApproved::dispatch($this->requisition, $this->actor);

            return $this->requisition;
        });
    }
}
```

- Name: `{Verb}Job` (`ApproveJob`, `RejectJob`, `CancelJob`, `CompleteJob`, `RecallJob`, `RequestReviewJob`).
- The signature is `(Model $model, User $actor, array $data = [])`. Pass the actor in; never call `auth()` or `Auth::` inside a job.
- **Mission status is a state machine** (`spatie/laravel-model-states`, `App\States\Mission\*`). The allowed moves live only in `MissionState::config()`. Action jobs fill any other fields, then call `$mission->status->moveTo(PRFMissionStatus::X)`. Check with `$mission->status->canMoveTo(...)` and compare with `$mission->status->is(...)`. `MissionObserver::updating()` refuses any direct `status` write that isn't an allowed move. The stored values are still the `PRFMissionStatus` integers.

### 3. Work job (queued): external I/O or heavy processing

```php
#[Queue('long')]
#[Tries(3)]
#[Backoff([30, 120, 300])]
#[Timeout(300)]
class GenerateExecutiveSummaryJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public function __construct(public Mission $mission) {}

    public function uniqueId(): string
    {
        return $this->mission->ulid;
    }

    public function handle(AIServiceInterface $ai): void { /* … */ }

    public function failed(Throwable $exception): void
    {
        Log::error('Executive summary failed', ['mission' => $this->mission->ulid, 'error' => $exception->getMessage()]);
    }
}
```

- Use `Illuminate\Foundation\Queue\Queueable` (never `Dispatchable`/`InteractsWithQueue`/`SerializesModels` by hand).
- The constructor takes **models**, not ids. Inject services into `handle()`.
- Queues:
  - `high`: payments and notifications
  - `default`
  - `long`: AI, media, exports
- `#[Timeout]` must stay under 600s, because the queue's `retry_after` is 660.
- `ShouldBeUnique` always needs a `uniqueId()`.

### 4. Recalculate job (queued, idempotent): derived data

Name it `Recalculate{Thing}Job`, for example `RecalculateProgressJob`. It must give the same result however many times it runs.

## Domain events and listeners

Events are named `App\Events\{Model}\{Model}{PastTense}` and implement `ShouldDispatchAfterCommit`, so listeners never see uncommitted data. Who dispatches them:

- **Lifecycle events** (`MissionSubscriptionCreated`, `SchoolLocationChanged`, `MissionApproved`, `MemberDeleted`, …) come from the model's **observer**. Records are created and changed from many places (API jobs, admin forms, bulk actions, imports), so the observer is the one place that sees every change. The observer only maps "what changed" to "which event"; it holds no side effects itself.
- **Business actions** that are not a plain field change (`RequisitionApproved` with its ledger entry, `RequisitionRecalled`, `RequisitionReviewRequested`, `MemberOnboarded`) come from the **action job** that performs them.

Listeners are named `App\Listeners\{Model}\{Verb}{Object}`, for example `AnnounceApprovedMission` or `NotifyStakeholdersOfRecall`. Each one does **one** thing: send a notification, start a job, or broadcast. A listener that serves several events type-hints a union: `handle(MissionServiced|MissionPostponed|MissionCancelled $event)`. Listeners are auto-discovered, so don't register them with `Event::listen`. Heavy work belongs in a queued job the listener dispatches.

**Broadcasts.** Real-time events implement `ShouldBroadcast`, keep the `PRFLiveEvent` payload shape, and are dispatched from listeners.

## Observers: map changes to events, keep the model consistent

Observers may derive columns (`full_name`), clear caches, cascade to rows the model owns, and dispatch lifecycle events. They must **not** send notifications, dispatch queued jobs or call external services; those go in listeners. Register an observer with `#[ObservedBy(XObserver::class)]` and delete empty stub methods.

## Scheduled commands

Commands that touch tenant data use the `RunsForEachTenant` trait. Without tenancy there is no tenant scope and no tenant integration keys. Commands are named `prf:{domain}:{action}`, return `int` from `handle()`, and are scheduled in `routes/console.php` with `withoutOverlapping()->onOneServer()`.

## Testing side effects

- **Job tests:** `Event::fake([MissionApproved::class])`, run the job, then `Event::assertDispatched(...)`.
- **Listener tests:** create the event, call `(new Listener)->handle($event)`, and assert with `Notification::fake()` or `Queue::fake()`.
