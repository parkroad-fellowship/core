<laravel-boost-guidelines>
=== .ai/mago/core rules ===

# Mago Formatter and PHPStan

This project formats PHP with **Mago**. Laravel Pint is not installed. Don't run `vendor/bin/pint`.

- After changing PHP files, run `vendor/bin/mago fmt` before you finish.
- To check formatting without changing files, run `vendor/bin/mago fmt --check`. The Check Formatting CI workflow enforces this.
- Static analysis: `make stan` (PHPStan + Larastan, **level 10**). `phpstan-baseline.neon` freezes the legacy errors; new and changed code must pass without adding to it. Never regenerate the baseline to hide a new error. The custom rule `NoQueryBuilderWritesRule` rejects query-level writes in jobs, listeners and actions.

=== .ai/prf/architecture rules ===

# PRF API Architecture

PRF is a multi-tenant Laravel 13 API with a Filament 5 admin panel. There is one Postgres database. Each tenant table has `tenant_id`, protected by Row Level Security (RLS). Mobile apps (PRF Missions, PRF Leadership, PRF Students) use the versioned REST API.

Before writing new code, copy the nearest sibling file. Every rule below matches the majority of the codebase. Anything that doesn't match is legacy and must not be copied.

## Request flow

```
routes/api/v1.php → Controller (extends App\Http\Controllers\Controller)
  → FormRequest (validates + authorizes via Model::permission())
  → {Domain}\{Verb}Job::dispatchSync() (all writes)
  → domain event → queued listeners (side effects)
  → re-fetch with QueryBuilder + INCLUDES → {Domain}\Resource
```

## Base controller: you get index, show and destroy for free

`app/Http/Controllers/Controller.php` implements `index`, `show` and `destroy`. A resource controller sets two properties and adds only `store` and `update`:

```php
class DepartmentController extends Controller
{
    protected ?string $modelClass = Department::class;

    protected ?string $resourceClass = Resource::class;

    public function store(CreateRequest $request): JsonResponse
    {
        $department = CreateJob::dispatchSync($request->validated());

        return $this->showResource($department->ulid)->response()->setStatusCode(201);
    }

    public function update(UpdateRequest $request, string $ulid): Resource
    {
        UpdateJob::dispatchSync($request->validated(), $ulid);

        return $this->showResource($ulid);
    }
}
```

- `index` authorizes `viewAny` and uses `Spatie\QueryBuilder`:
  - `?include=a,b` is limited to `Model::INCLUDES`
  - `?filter[x]=` uses `Model::filters()`
  - `?sort=-created_at` is limited to `Model::SORTS`
  - `?limit=` defaults to `$defaultLimit = 15` and uses `simplePaginate`
- Change the defaults with `protected int $defaultLimit` or `protected string $defaultSort`.
- `show` authorizes `view`. `destroy` authorizes `delete`, soft deletes, and returns 204.
- Controller method signatures are always `(FormRequest $request, string $ulid)`: the request first, then the ULID. Look records up by ULID, never by id, and never use implicit route model binding.
- Controllers never write to models. Every write goes through a job.
- **Controllers are CRUD per model.** Before adding a custom action, model the action as its own resource. Sending a receipt means creating a `ReceiptDelivery`, generating a report means creating a `FinancialReport`, and moving money means creating an `AccountTransfer`. Custom `POST /{ulid}/{verb}` actions are reserved for genuine state transitions on the same model (approve, reject, recall) and need a reason.

## Routes

- New v1 resources go inside the existing protected group in `routes/api/v1.php`, which already applies `tenant.initialized`, `auth:sanctum` and `tenant.validate`. Add a group with `'prefix' => 'v1/{kebab-plural}'` and `'as' => 'api.{kebab-plural}.'`.
- Use `{ulid}` parameters. Updates use `Route::match(['put', 'patch'], '/{ulid}', …)`. The rare state-transition actions are `POST /{ulid}/{verb}`.
- Give every route a `->name()`. Tests and code use `route()`, never hard-coded URLs.
- `routes/api/v2.php` is for media endpoints only and uses the same tenant middleware.
- Public endpoints (webhooks, the public pledge form) must call `->withoutMiddleware(VerifyRequestSignature::class)`. Otherwise every API request needs the `X-PRF-Signature`, `X-PRF-Timestamp` and `X-PRF-App-ID` headers as soon as an `APIClient` row exists.

## Models

```php
#[Fillable(['name', 'is_active'])]
#[ObservedBy(DepartmentObserver::class)]
class Department extends Model implements HasQueryBuilderCapabilities
{
    use BelongsToTenant;
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;
    use HasModelPermissions;
    use HasULID;
    use LogsActivity;
    use SoftDeletes;

    public const INCLUDES = ['members'];

    public const SORTS = ['created_at', 'updated_at', 'name'];

    /** @return array<int, AllowedFilter> */
    public static function filters(): array
    {
        return [AllowedFilter::exact('is_active')];
    }

    protected function casts(): array
    {
        return ['is_active' => PRFActiveStatus::class];
    }

    /** @return BelongsToMany<Member, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
```

- **Attributes over properties.** Use Laravel 13 attributes: `#[Fillable]`, `#[Hidden]`, `#[Appends]`, `#[Table]`, `#[ObservedBy(X::class)]` (one class, no array), `#[UsePolicy]` and `#[Scope]` for local scopes. Casts go in a `casts()` method. Accessors use `Attribute::make()`. Constants are `public const`.
- **Traits.** Import them from `App\Models\Concerns`:
  - `HasULID` generates a lowercase ULID, uses it as the route key, and adds `findByULID()`.
  - `HasModelPermissions` gives `Mission::permission('create')`, which returns `'create mission'`.
- **Tenancy.** Every tenant model uses `Stancl\Tenancy\Database\Concerns\BelongsToTenant`. Never set `tenant_id` by hand.
- **Relations.** Every relation has a native return type and a generic `@return` PHPDoc.
- **Polymorphic models.** Add a case to `App\Enums\PRFMorphType`, which is the morph map.
- **Media.** Collection names are `public const` kebab-case strings, listed in `MEDIA_COLLECTIONS` and registered in `registerMediaCollections()`.

## Migrations

Tenant tables need these columns: `$table->id()`, `$table->ulid()->unique()`, `$table->foreignId(...)` for relations, `$table->string('tenant_id', 36)` plus a foreign key to `tenants` with `cascadeOnDelete()` and an index, `$table->timestamps()` and `$table->softDeletes()`. Money is **whole KES** stored in `bigInteger`/`unsignedBigInteger` columns (never decimals or cents). Name anonymous migrations `create_{table}_table` or `add_{column}_to_{table}_table`.

## Form Requests

These live in `app/Http/Requests/{Domain}/`: `CreateRequest`, `UpdateRequest`, and `{Verb}Request` for actions.

```php
public function authorize(): bool
{
    return $this->user()->can(Department::permission('create'));
}

/** @return array<string, ValidationRule|array<mixed>|string> */
public function rules(): array
{
    return [
        'name' => ['required', 'string', 'max:255'],
        'member_ulid' => ['required', 'string', 'exists:members,ulid'],
    ];
}
```

- Write rules with array syntax, never pipe strings. Refer to related records by `*_ulid`, and have the job convert ULIDs to IDs.
- Authorize with `Model::permission()` (actions: `viewAny`, `view`, `create`, `edit`, `delete`, `restore`, `forceDelete`, plus custom ones). Never hand-type a permission string.
- Custom rules go in `app/Rules/{Domain}/`.

## Permissions and policies

- Permissions are defined per role in `config/prf/roles.php`, named `"{action} {entity}"`, where the entity comes from `permissionEntity()`. A new resource must add its seven CRUD permissions to `super admin` and to the relevant desk roles.
- A policy is a one-liner that extends `BasePolicy` (which lets `super admin` through). Policies are auto-discovered: `class DepartmentPolicy extends BasePolicy { protected string $modelClass = Department::class; }`.
- Filament checks use `userCan(Department::permission('create'))`, never a literal string.

## API Resources

This is `app/Http/Resources/{Domain}/Resource.php`:

```php
return [
    'entity' => 'mission-session',           // kebab-case, always present
    'ulid' => $this->ulid,                   // never expose `id`
    'status' => $this->status?->value,       // enums as their backing value
    'mission' => new \App\Http\Resources\Mission\Resource($this->whenLoaded('mission')),
    'members_count' => $this->whenCounted('members'),
    'created_at' => $this->created_at,
    'updated_at' => $this->updated_at,
];
```

Every resource class is named `Resource`, and each domain has its own namespace. Refer to another domain's resource by its **fully qualified name** (`new \App\Http\Resources\Mission\Resource(...)`, `\App\Http\Resources\Media\Resource::collection(...)`), and never alias resource imports with `use … as XResource`. Only the controller's own domain resource is imported (`use App\Http\Resources\Mission\Resource;`). Never write a query inside a resource: use `whenLoaded` or `whenCounted`.

## Enums

These live in `app/Enums/PRF{Name}.php`. They are int-backed, with string backing only where the value must be a string. Cases are SCREAMING_CASE. Helpers are `getOptions()`, `getLabel()`, `getColor()` (Filament colours), `fromValue()` and `getElements()`. Compare against enum cases, not magic numbers.

## Services and contracts

- Anything that talks to an external provider sits behind an interface in `app/Contracts/Services/` and is bound in `AppServiceProvider::register()`.
- Inject interfaces through constructors or `handle()`. Don't call `app(X::class)` in application code.
- Multi-provider services (SMS, AI) use an `Illuminate\Support\Manager` or the `laravel/ai` SDK. Follow the Tenancy & Integrations guide.

## Filament

- **Resources:**
  - Tenant resources: `app/Filament/Resources/{Plural}/{Model}Resource.php` with `Pages/` (List/Create/Edit/View) and `RelationManagers/`.
  - Lookup tables: `MasterDataCluster`.
  - Settings: `SystemSettingsCluster`.
  - Central panel resources: `app/Filament/Central/`.
- Reuse the shared schemas in `app/Filament/Forms/Schemas/` (`StatusSchema`, `ContentSchema`, `LocationSchema`, …).
- Filament actions call the same jobs as the API, for example `ApproveJob::dispatchSync($record, auth()->user())`. They never call `$record->update(['status' => …])` directly.

## Conventions

- **Acronyms are fully capitalised** in class, trait, enum, interface and method names: `SMSManager`, `SendSMSJob`, `APIClient`, `PRFEvent`, `HasULID`, `SMSLog`, `generateULID()`, `findByULID()`. Database columns, array keys and config keys stay snake_case (`ulid`, `sms_logs`).

- Use `Auth::` and `now()`. Carbon is mutable: call `->copy()` before `addDays()` and friends. Use `$from->diffInDays($to, absolute: true)`, because the default is signed in Carbon 3.
- Helpers: `App\Helpers\Utils` (static), plus `userCan()`, `generatePdf()` and `tenant_asset()`.
- Keep PHPDoc short and don't write "Create a new job instance." boilerplate.
- Format with Mago and keep PHPStan at level 10 (see the Mago guideline).

=== .ai/prf/finance rules ===

# Treasurer finance

The fellowship's money is kept in one **ledger** (`ledger_entries`): every shilling into or out of a `FinancialAccount` (Paybill, M-Pesa, Bank, Cash, M-Shwari, Paystack). Balances, statements, receipts and reports all come from it.

## Writing to the ledger

- **Only `App\Services\Finance\Ledger::post()` writes `ledger_entries`**, always from a job:
  - `LedgerEntry\CreateJob` is for lines the treasurer keys in.
  - `Ledger::postPayment()`, `postDisbursement()`, `postRefund()` and `postToken()` handle auto-posting.
- `post()`:
  - numbers income receipts (`PRF-2026-000001`)
  - defaults the flow from the category kind and the channel from the account type
  - is idempotent through `source_key`: auto-posted lines always set one, e.g. `payment:{id}:gift`, `refund:{id}:refund`, `import:{hash}`.
- Amounts are **whole KES** and always positive. `flow` (`PRFLedgerFlow`) gives the direction.
- **Categories and accounts:** find the ones the app posts to through `ChartOfAccounts`, e.g. `category('income.appreciation_from_schools')`, `expenseFor($desk)`, `refundFor($desk)`, `account(PRFFinancialAccountType::PAYSTACK)`.
  - Coded categories come from `config/prf/finance.php` and are seeded for every tenant by `TenantReferenceDataSeeder`.
  - They may be renamed but never deleted (`LedgerCategoryPolicy`).

## Accounting rules

- **Income** is only RECEIPT lines in INCOME categories.
- **Refunds** (`REFUND` kind) return money to a desk. They reduce that desk's expense line and are never income. A refund first covers any tokens of appreciation the missioner collected but didn't hand over; that part is booked as Appreciation From Schools income.
- **Transfers** between the fellowship's own accounts go through `AccountTransfer`: an out/in pair plus a CHARGE line. They are neither income nor expense.
- **Opening balances** are OPENING_BALANCE lines and stay off the income statement.
- **Paystack** is booked **gross** as income, with Paystack's fee as a Treasurer's Desk charge. The settlement to the bank is an `AccountTransfer`.
- **Disbursing a requisition** is a PAYMENT in the desk's expense category, linked to its accounting event (`ApproveJob` with `financial_account_ulid`, or `RecordDisbursementJob`).
- **Real spending** per accounting event comes from allocation entries (`AccountabilityService`), not the ledger. The ledger is cash-basis.

## Receipts, reports and imports

- **Receipts** are sent by creating a `ReceiptDelivery` (email with the PDF, SMS with a signed link, or a WhatsApp share link the treasurer sends from their own phone). There is no WhatsApp API. Never send receipts for imported rows.
- **Reports** are generated by creating a `FinancialReport`. `GenerateJob` builds the xlsx/pdf on the `long` queue, stores it on the tenant's `local` disk and emails it. Never stream a finance export from a controller.
- **The treasurer's workbook** is imported through Treasurer → Import Workbook (`WorkbookImporter`, `ImportWorkbookJob`). The Handover sheet is never read, and uploads are deleted once the import finishes.

## The web panel is the treasurer's interface

Every treasurer task must be possible in the Filament **Treasurer** group. The API mirrors the same CRUD resources for the mobile apps. Filament forms call the same jobs as the API.

=== .ai/prf/jobs-and-side-effects rules ===

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

=== .ai/prf/notifications rules ===

# Notifications

## Naming

`app/Notifications/{Model}/{Model}{Event}Notification.php`. The folder is the model the constructor takes, and the class name repeats it so imports never collide:

- `Mission/MissionApprovedNotification`
- `Requisition/RequisitionRejectedNotification`
- `AccountingEvent/AccountingEventCreatedNotification`
- `Pledge/PledgeDueNotification`

## Shape

Every notification extends `App\Notifications\BaseNotification`:

```php
class MissionApprovedNotification extends BaseNotification
{
    public function __construct(public Mission $mission) {}

    public function type(): PRFNotificationType
    {
        return PRFNotificationType::MISSION_APPROVED;
    }

    public function targetApp(object $notifiable): PRFAppTopics
    {
        return PRFAppTopics::MISSIONS_APP;
    }

    public function toMail(object $notifiable): MailMessage { /* … */ }

    protected function fcmTitle(): string { /* … */ }

    protected function fcmBody(): string { /* … */ }
}
```

`BaseNotification` handles the rest:
- it is queued on `high`;
- `via()` sends mail, plus FCM when the tenant has FCM configured and the notifiable has tokens;
- the FCM payload uses `type()->value`.

Don't add empty `toArray()` stubs.

## Recipients

- **Members and users:** `Notification::send($members, new …)`. Mail goes to the address from `Member::routeNotificationForMail()`, which depends on the tenant's email mode.
- **Desk mailboxes:** `Notification::route('mail', Utils::getDeskEmails(PRFResponsibleDesk::MISSIONS))->notify(new …)`. Read desk emails through `Utils::getDeskEmails()` only.
- **A raw email address** (for example a pledger): `Notification::route('mail', $pledge->email)`. Never pass a string to `Notification::send`.

## Where they are sent from

Notifications are sent from **queued listeners** reacting to domain events (see Jobs & Side Effects). Never send them from observers, controllers or Filament actions.

## Secrets

Never put a password or token in a queued notification's constructor, because it would be stored in the `jobs` and `failed_jobs` tables. If you must send one, call `Notification::sendNow()` from inside the job that generated it.

=== .ai/prf/tenancy-and-integrations rules ===

# Tenancy and integrations

## Tenancy basics

- The app uses `stancl/tenancy` with **one shared Postgres database**. Each tenant table has a `tenant_id` column, scoped by the `BelongsToTenant` global scope and enforced by Postgres RLS.
- Identity data (`users`, `roles`, `permissions`, `connected_accounts`) lives on the central connection through `HasCrossDomainConnection`. A single `User` can belong to several tenants through `tenant_user`, and Spatie permission teams equal the tenant id.
- **Requests:**
  - API requests pick up their tenant through the `tenant.initialized` + `tenant.validate` middleware.
  - Webhooks use a tenant in the path (`/v1/paystack/{tenant}/ipn`) with `InitializeTenancyByPath`.
- **Queued jobs** dispatched inside a tenant run inside that tenant (`QueueTenancyBootstrapper`).
- **Scheduled commands** have no tenant. They must use the `RunsForEachTenant` trait.
- **Tenant settings** live in `AppSetting` (key/value, cached per tenant). Read them with `AppSetting::get('key')`, or with the typed helpers in `App\Settings\TenantSettings`.

## Per-tenant integrations fail closed

Integrations owned by each tenant are listed in `App\Enums\PRFIntegration`: `PAYSTACK`, `SMS`, `FCM`, `AI`, and `GOOGLE_WORKSPACE` (only in org-domain email mode).

- Each tenant enters its own keys in Filament → App Settings. Secret values are stored encrypted.
- **There is no fallback to `.env`.** When tenancy starts, `TenantIntegrations::load()` writes the tenant's values into `config()`. Missing values become `null`. When tenancy ends, `TenantIntegrations::reset()` clears them, so one tenant's keys can never leak into another tenant's job.
- **Before using an integration**, call `app(TenantIntegrations::class)->require(PRFIntegration::PAYSTACK)`. If the tenant hasn't configured it, this throws `IntegrationNotConfiguredException`:
  - API requests get a 422 with a clear message.
  - Queued jobs and listeners log it and skip.
- Use `isConfigured()` to decide whether to offer a channel at all. For example, FCM is only added to `via()` when it is configured.

**Platform integrations** are owned by PRF and read only from `config/prf/*.php` (env): NLP, Azure Speech, TomorrowIO weather, Google Maps/Sheets/Drive, Turnstile, mail, storage and Reverb. Never read `env()` outside `config/`.

## Adding a provider

**SMS:**
- Extend `App\Services\SMS\SMSGateway` and implement only `deliver(string $e164, string $message): SMSResult`.
- The base class already handles phone normalisation, the test-number override outside production, and the `SMSLog` row.
- Register the driver with `createXDriver()` on `SMSManager`, or with `SMSManager::extend()`.
- Add its keys to `PRFIntegration::SMS`.

**AI:**
- The `laravel/ai` SDK handles the provider. Tenants set `ai.provider`, `ai.endpoint`, `ai.model` and `ai.api_key`, and can override the model per feature with `ai.models.{feature}`.
- The default provider is `azure-foundry`: an Azure AI Foundry deployment (for example DeepSeek), called through `{endpoint}/openai/v1/chat/completions` with an `api-key` header. `ai.model` is the deployment name. Any `Laravel\Ai\Enums\Lab` provider (openai, anthropic, gemini, …) also works.
- `LaravelAIService` registers the tenant's credentials as the `tenant` provider instance and purges it whenever tenancy changes.
- Callers depend on `AIServiceInterface` (`text()` / `structured()`) and pass an `AIPrompt`.
- Prompts live in `app/AI/Prompts/{Feature}Prompt.php`. Never inline them in jobs, and never call a provider's HTTP API directly.

**Payments:** go through `PaymentGatewayInterface`. Payment status changes only through `Payment\ApplyGatewayStatusJob`, which never downgrades a terminal status.

## Member email modes

Each tenant sets `organization.member_email_mode` (`PRFMemberEmailMode`):

- **`ORGANISATION_DOMAIN`:**
  - The tenant owns a Google Workspace domain (`organization.org_email_domain`).
  - `MemberIdentityService` allocates `first.last@domain` after checking local users **and** Workspace.
  - A queued listener then creates the Workspace account.
- **`PERSONAL`:**
  - The member's `personal_email` is their identity (for example their own Gmail).
  - Nothing is generated, and one `User` is shared if the same person belongs to several tenants.

Never generate addresses on public webmail domains. `Utils::getOrgEmailDomain()` returns `null` for them, and for tenants in personal mode. Access to the tenant admin panel is controlled by the `access tenant panel` permission, never by email domain.

=== .ai/prf/testing rules ===

# Testing

## Running tests: always in Docker

Tests run against **Postgres 16 with RLS**, in the Docker test environment (`Dockerfile.test` + `docker-compose.test.yml`), exactly like CI. Don't run the suite directly on the host: SQLite hides RLS and per-tenant unique-index behaviour.

```bash
make test-build                                      # once, and after Dockerfile.test changes
make test-file FILE=tests/Feature/Api/DepartmentTest.php
make test-filter FILTER="creates a department"
make test                                            # full suite (run before finishing a task)
make stan                                            # PHPStan level 10
```

The container mounts the repository and uses the host's `vendor/`, so run `composer install` on the host after dependency changes. Tests must never call real providers:
- Paystack and HTTP: `Http::fake()`
- AI: the `laravel/ai` fakes
- Workspace: `Tests\Fakes\FakeWorkspaceDirectory`
- SMS: the fake driver
- PDFs (Gotenberg): call `fakePDFRendering()` from `tests/Pest.php`; views still render

## Layout

| Directory | Contents |
|---|---|
| `tests/Feature/Api/{Domain}Test.php` | HTTP API tests, one file per resource |
| `tests/Feature/{Tenancy,Webhooks,Filament,Members,Payments,Listeners,Jobs}/` | cross-cutting feature tests |
| `tests/Unit/` | pure unit tests (no HTTP) |
| `tests/Services/` | service implementations and bindings |

## Coverage you get for free

Two route-wide tests run against every API resource automatically:
- `tests/Feature/Api/AuthorizationTest.php`: every index served by the base controller refuses a user without permissions.
- `tests/Feature/Api/ValidationTest.php`: every create endpoint with required fields answers an empty payload with 422, never 500.

A new resource is covered as soon as its routes exist. Resource test files focus on behaviour.

## API test template

Every API test file follows this shape. Copy `tests/Feature/Api/DepartmentTest.php`.

```php
use App\Models\Department;

describe('index', function () {
    it('lists departments', function () {
        Department::factory()->count(3)->create();

        actingAsTenantUser()->getJson(route('api.departments.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => ['*' => ['entity', 'ulid', 'name']]]);
    });

    it('forbids users without permission', function () {
        actingAsTenantUser([])->getJson(route('api.departments.index'))->assertForbidden();
    });
});

describe('store', function () {
    it('creates a department', function () {
        actingAsTenantUser()->postJson(route('api.departments.store'), ['name' => 'Choir'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Choir');

        expect(Department::query()->where('name', 'Choir')->exists())->toBeTrue();
    });

    it('validates input', function (array $payload, string $field) {
        actingAsTenantUser()->postJson(route('api.departments.store'), $payload)
            ->assertJsonValidationErrors($field);
    })->with([
        'missing name' => [[], 'name'],
        'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    ]);
});

describe('show', function () { /* returns the resource by ulid; 404 for unknown ulid */ });
describe('update', function () { /* updates + asserts the change; validation dataset */ });
describe('destroy', function () { /* assertNoContent + assertSoftDeleted */ });
```

## Rules

- Write test names in the present tense: `it('creates a department')`, not `it('should create …')`.
- Always use `route('api.…')` and never hard-code `/api/v1/…` URLs.
- Build data with factories and their states, never `inRandomOrder()`.
- Keep one behaviour per test.
- **Helpers** (in `tests/Pest.php`):
  - `actingAsTenantUser(array $roles = ['super admin', 'member'])`
  - `actingAsTenantUser([])` for a user with no roles, to test 403s
  - `tenantHeaders()`
  - `createOrGetTenant()`
  - `createTenant()` and `tenantUser($tenant, $roles)` for multi-tenant tests
  - `tests/Feature/Finance` gets roles and the chart of accounts from a shared `beforeEach`
- **Side effects:**
  - In job tests: `Event::fake([...])`, then `Event::assertDispatched(...)`.
  - In listener tests: `Notification::fake()` or `Queue::fake()`.
  - After-commit events still run in tests (`RefreshDatabase`).
- Every change ships with a test. Run only the affected file while working, then run `make test` before finishing.

=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.4. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `bun run build`, `bun run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record a rule with `record-rule` only when the user explicitly asks for one. Instructions for the work at hand are not rules, no matter how emphatic: "remove this typo", "use X here" are work to do, not rules to record. Never record a rule on your own initiative, as a byproduct of a change, or to summarize what you just did. When the user does ask, pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Use `record-rule` rather than your native memory or notes tool, because native memory is personal and session-scoped, while only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.
- Activate the `deploying-to-cloud` skill whenever deploying to Laravel Cloud, configuring Cloud environments or resources, using the Cloud CLI, or troubleshooting Cloud deployments.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `bun run build` or ask the user to run `bun run dev` or `composer run dev`.

=== livewire/core rules ===

# Livewire

- Livewire allows you to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.
- After the feature tests pass, ask the user to run the complete suite with `php artisan test --compact`.

=== filament/filament/core rules ===

## Filament

- Filament is a Laravel UI framework built on Livewire, Alpine.js, and Tailwind CSS. UIs are defined in PHP via fluent, chainable components. Follow existing conventions in this app.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices. If `search-docs` is unavailable, refer to https://filamentphp.com/docs.

### Artisan

- Always use Filament-specific Artisan commands to create files. Find available commands with the `list-artisan-commands` tool, or run `php artisan --help`.
- Inspect required options before running, and always pass `--no-interaction`.

### Patterns

Always use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field visibility" lang="php">
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

Select::make('type')
    ->options(CompanyType::class)
    ->required()
    ->live(),

TextInput::make('company_name')
    ->required()
    ->visible(fn (Get $get): bool => $get('type') === 'business'),

</code-snippet>

Use `Set $set` inside `->afterStateUpdated()` on a `->live()` field to mutate another field reactively. Prefer `->live(onBlur: true)` on text inputs to avoid per-keystroke updates:

<code-snippet name="Reactive field update" lang="php">
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;

TextInput::make('title')
    ->required()
    ->live(onBlur: true)
    ->afterStateUpdated(fn (Set $set, ?string $state) => $set(
        'slug',
        Str::slug($state ?? ''),
    )),

TextInput::make('slug')
    ->required(),

</code-snippet>

Compose layout by nesting `Section` and `Grid`. Children need explicit `->columnSpan()` or `->columnSpanFull()`:

<code-snippet name="Section and Grid layout" lang="php">
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

Section::make('Details')
    ->schema([
        Grid::make(2)->schema([
            TextInput::make('first_name')
                ->columnSpan(1),
            TextInput::make('last_name')
                ->columnSpan(1),
            TextInput::make('bio')
                ->columnSpanFull(),
        ]),
    ]),

</code-snippet>

Use `Repeater` for inline `HasMany` management. `->relationship()` with no args binds to the relationship matching the field name:

<code-snippet name="Repeater for HasMany" lang="php">
use Filament\Forms\Components\Repeater;

Repeater::make('qualifications')
    ->relationship()
    ->schema([
        TextInput::make('institution')
            ->required(),
        TextInput::make('qualification')
            ->required(),
    ])
    ->columns(2),

</code-snippet>

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column value" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),

</code-snippet>

Use `SelectFilter` for enum or relationship filters, and `Filter` with a `->query()` closure for custom logic:

<code-snippet name="Table filters" lang="php">
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

SelectFilter::make('status')
    ->options(UserStatus::class),

SelectFilter::make('author')
    ->relationship('author', 'name'),

Filter::make('verified')
    ->query(fn (Builder $query) => $query->whereNotNull('email_verified_at')),

</code-snippet>

Actions are buttons that encapsulate optional modal forms and behavior:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;

Action::make('updateEmail')
    ->schema([
        TextInput::make('email')
            ->email()
            ->required(),
    ])
    ->action(fn (array $data, User $record) => $record->update($data)),

</code-snippet>

### Testing

Testing setup (requires `pestphp/pest-plugin-livewire` in `composer.json`):

- Always call `$this->actingAs(User::factory()->create())` before testing panel functionality.
- For edit pages, pass `['record' => $user->id]`, use `->call('save')` (not `->call('create')`), and do not assert `->assertRedirect()` (edit pages do not redirect after save).

<code-snippet name="Table test" lang="php">
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->searchTable($users->first()->name)
    ->assertCanSeeTableRecords($users->take(1))
    ->assertCanNotSeeTableRecords($users->skip(1));

</code-snippet>

<code-snippet name="Create resource test" lang="php">
use function Pest\Laravel\assertDatabaseHas;

livewire(CreateUser::class)
    ->fillForm([
        'name' => 'Test',
        'email' => 'test@example.com',
    ])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors()
    ->assertRedirect();

assertDatabaseHas(User::class, [
    'name' => 'Test',
    'email' => 'test@example.com',
]);

</code-snippet>

<code-snippet name="Edit resource test" lang="php">
livewire(EditUser::class, ['record' => $user->id])
    ->fillForm(['name' => 'Updated'])
    ->call('save')
    ->assertNotified()
    ->assertHasNoFormErrors();

assertDatabaseHas(User::class, [
    'id' => $user->id,
    'name' => 'Updated',
]);

</code-snippet>

<code-snippet name="Testing validation" lang="php">
livewire(CreateUser::class)
    ->fillForm([
        'name' => null,
        'email' => 'invalid-email',
    ])
    ->call('create')
    ->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ])
    ->assertNotNotified();

</code-snippet>

Use `->callAction(DeleteAction::class)` for page actions, or `->callAction(TestAction::make('name')->table($record))` for table actions:

<code-snippet name="Calling actions" lang="php">
use Filament\Actions\Testing\TestAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('promote')->table($user), [
        'role' => 'admin',
    ])
    ->assertNotified();

</code-snippet>

### Correct Namespaces

- Form fields (`TextInput`, `Select`, `Repeater`, etc.): `Filament\Forms\Components\`
- Infolist entries (`TextEntry`, `IconEntry`, etc.): `Filament\Infolists\Components\`
- Layout components (`Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.): `Filament\Schemas\Components\`
- Schema utilities (`Get`, `Set`, etc.): `Filament\Schemas\Components\Utilities\`
- Table columns (`TextColumn`, `IconColumn`, etc.): `Filament\Tables\Columns\`
- Table filters (`SelectFilter`, `Filter`, etc.): `Filament\Tables\Filters\`
- Actions (`DeleteAction`, `CreateAction`, etc.): `Filament\Actions\`. Never use `Filament\Tables\Actions\`, `Filament\Forms\Actions\`, or any other sub-namespace for actions.
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

### Common Mistakes

- **Never assume public file visibility.** File visibility is `private` by default. Always use `->visibility('public')` when public access is needed.
- **Never assume full-width layout.** `Grid`, `Section`, `Fieldset`, and `Repeater` do not span all columns by default.
- **Use `Select::make('author_id')->relationship('author', 'name')` for BelongsTo fields.** `BelongsToSelect` does not exist in v4.
- **`Repeater` uses `->schema()`, not `->fields()`.**
- **Never add `->dehydrated(false)` to fields that need to be saved.** It strips the value from form state before `->action()` or the save handler runs. Only use it for helper/UI-only fields.
- **Use correct property types when overriding `Page`, `Resource`, and `Widget` properties.** These properties have union types or changed modifiers that must be preserved:
  - `$navigationIcon`: `protected static string | BackedEnum | null` (not `?string`)
  - `$navigationGroup`: `protected static string | UnitEnum | null` (not `?string`)
  - `$view`: `protected string` (not `protected static string`) on `Page` and `Widget` classes

=== spatie/laravel-activitylog/core rules ===

# spatie/laravel-activitylog

Activity logging package for Laravel. Logs model events and manual activities to a database table.

## Key Concepts

- **Activity**: An Eloquent model (`Spatie\Activitylog\Models\Activity`) storing log entries with subject, causer, event, attribute_changes, and properties.
- **Subject**: The model being acted upon (polymorphic `subject_type`/`subject_id`).
- **Causer**: The model that caused the action, typically the authenticated user (polymorphic `causer_type`/`causer_id`).
- **LogOptions**: Fluent configuration object returned by `getActivitylogOptions()` on models using the `LogsActivity` trait.
- **ActivityEvent**: Enum with cases `Created`, `Updated`, `Deleted`, `Restored`.
- **`attribute_changes`** column: stores `{"attributes": {...}, "old": {...}}` for tracked model changes.
- **`properties`** column: stores custom user data set via `withProperties()`.

## Traits

### `LogsActivity`

Add to models to automatically log create/update/delete events. Optionally implement `getActivitylogOptions()` to configure which attributes to track (defaults to logging events without attribute changes).

```php
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Article extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
```

### `CausesActivity`

Add to user/causer models. Provides `activitiesAsCauser()` relationship.

### `HasActivity`

Combines `LogsActivity` and `CausesActivity`. Provides `activities()`, `activitiesAsSubject()`, and `activitiesAsCauser()`.

## Manual Logging

```php
activity()
    ->performedOn($article)
    ->causedBy($user)
    ->event(ActivityEvent::Updated)
    ->withProperties(['key' => 'value'])
    ->log('Article was updated');
```

## LogOptions Methods

| Method | Description |
|--------|-------------|
| `logFillable()` | Log all fillable attributes |
| `logAll()` | Log all attributes |
| `logOnly(array)` | Log specific attributes |
| `logExcept(array)` | Exclude attributes |
| `logOnlyDirty()` | Only log changed attributes |
| `dontLogEmptyChanges()` | Skip logging when no tracked attributes changed |
| `dontLogIfAttributesChangedOnly(array)` | Ignore updates that only change these attributes |
| `useLogName(string)` | Set custom log name |
| `setDescriptionForEvent(Closure)` | Custom description per event |
| `useAttributeRawValues(array)` | Store raw (uncast) values |

## Querying Activities

```php
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Enums\ActivityEvent;

Activity::forEvent(ActivityEvent::Created)->get();
Activity::causedBy($user)->get();
Activity::forSubject($article)->get();
Activity::inLog('orders')->get();
```

## Setting the causer

Override the causer for a block of code:

```php
use Spatie\Activitylog\Facades\Activity;

Activity::defaultCauser($admin, function () {
    // all activities here are caused by $admin
});

// or set globally for the rest of the request
Activity::defaultCauser($admin);
```

## Disabling Logging

```php
activity()->withoutLogging(function () {
    // no activities logged here
});
```

## Accessing Changes and Properties

```php
$activity = Activity::latest()->first();

// Tracked model changes (set automatically by LogsActivity)
$activity->attribute_changes; // Collection: {"attributes": {...}, "old": {...}}

// Custom user data (set via withProperties)
$activity->properties; // Collection
$activity->getProperty('key'); // single value
```

## Custom Activity Model

Set `activity_model` in `config/activitylog.php` to a class that extends `Model` and implements `Spatie\Activitylog\Contracts\Activity`. Use a custom model for custom table names or database connections.

## Customizing Actions

The package uses action classes (`LogActivityAction`, `CleanActivityLogAction`) that can be extended and swapped via config:

```php
// config/activitylog.php
'actions' => [
    'log_activity' => \App\Actions\CustomLogActivityAction::class,
    'clean_log' => \App\Actions\CustomCleanAction::class,
],
```

Custom action classes must extend the originals. Override protected methods (`save()`, `beforeActivityLogged()`, `resolveDescription()`, etc.) to customize behavior.

## Configuration

Key config options in `config/activitylog.php`:
- `enabled`: Master on/off switch (env: `ACTIVITYLOG_ENABLED`)
- `clean_after_days`: Days to keep records for `activitylog:clean` command
- `default_log_name`: Default log name (string)
- `default_auth_driver`: Auth driver for causer resolution
- `include_soft_deleted_subjects`: Include soft-deleted subjects
- `activity_model`: Custom Activity model class
- `default_except_attributes`: Globally excluded attributes
- `actions.log_activity`: Action class for logging activities
- `actions.clean_log`: Action class for cleaning old activities

=== spatie/laravel-medialibrary/core rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>
