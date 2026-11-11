<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

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

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `bun run build` or ask the user to run `bun run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

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

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

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

=== dedoc/scramble/core rules ===

## Scramble

This project uses `dedoc/scramble` to generate OpenAPI documentation from application code. Prefer inference over redundant annotations.

Follow the `scramble-development` skill when changing API endpoints or the resources, FormRequests, shared types, and authentication they use, or when configuring or troubleshooting Scramble documentation.

=== maatwebsite/excel/core rules ===

# Laravel Excel

- Use `maatwebsite/excel` for spreadsheet exports, imports, queued spreadsheet work, CSV handling, and PhpSpreadsheet integration in Laravel applications.
- Prefer explicit export/import classes with package concerns over ad-hoc spreadsheet generation in controllers, jobs, or commands.
- Activate the `laravel-excel` skill when working with `Excel::download()`, `Excel::store()`, `Excel::queue()`, `Excel::raw()`, `Excel::import()`, `Excel::toArray()`, `Excel::toCollection()`, export/import concerns, queued imports/exports, validation, CSV settings, styling, events, formulas, charts, drawings, multiple sheets, mapped cells, macros, config, cache, transactions, temporary files, or `Excel::fake()`.
- For broad docs, all-feature tasks, or missing-feature audits, use the skill's `references/package.md` feature matrix before answering.
- For large datasets, prefer `FromQuery` with queued exports or `WithChunkReading` and `WithBatchInserts` for imports.
- Test spreadsheet behavior with `Excel::fake()` when asserting dispatch/download/store/import intent, and inspect generated files only when cell contents, formatting, sheets, or writer behavior must be proven.

=== spatie/laravel-activitylog/core rules ===

## Activitylog

- `spatie/laravel-activitylog` logs model events and manual activities to a database table, with subjects, causers, tracked attribute changes, and custom properties.
- Always activate the `activitylog-development` skill when working with activity logging or any code that uses the `LogsActivity`, `CausesActivity`, or `HasActivity` traits, `LogOptions`, the `Activity` model, or the `activity()` helper.
- If the skill is unavailable, read `vendor/spatie/laravel-activitylog/resources/boost/skills/activitylog-development/SKILL.md` before making changes.

=== spatie/laravel-medialibrary/core rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>

# PRF SuperApp API - Architecture Guide

This section describes the code architecture and patterns used in the PRF Laravel API so AI agents can generate code that matches the project format.

---

## Request Flow Overview

```
HTTP Request
    ↓
Route (routes/api/v1.php or v2.php)
    ↓
Controller (app/Http/Controllers/API/)
    ↓
Form Request (app/Http/Requests/) → Validation + Authorization
    ↓
Job::dispatchSync() (app/Jobs/) → Business Logic
    ↓
Model Operations → Observers may trigger side effects
    ↓
API Resource (app/Http/Resources/) → JSON Response
```

---

## 1. Routes

**Location:** `routes/api/v1.php`, `routes/api/v2.php`

**Pattern:**
```php
Route::group([
    'prefix' => 'v1/missions',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.missions.',
], function () {
    Route::get('/', [MissionController::class, 'index']);
    Route::post('/', [MissionController::class, 'store']);
    Route::get('/{ulid}', [MissionController::class, 'show']);
    Route::match(['put', 'patch'], '/{ulid}', [MissionController::class, 'update']);
    Route::delete('/{ulid}', [MissionController::class, 'destroy']);

    // Custom actions
    Route::post('/{ulid}/approve', [MissionController::class, 'approve']);
});
```

**Key Points:**
- Use `auth:sanctum` middleware for protected routes
- Use ULID string parameters (NOT implicit route model binding)
- Group routes by resource with prefix and named routes
- Custom actions use POST with descriptive names

---

## 2. Controllers

**Location:** `app/Http/Controllers/API/`

**Pattern:**
```php
class RequisitionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 100);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $items = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(Requisition::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('status', fn ($query, $value) => ...),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($items);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();
        $item = CreateJob::dispatchSync($validated);

        // Reload with eager loading
        $item = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(Requisition::INCLUDES)
            ->where('ulid', $item->ulid)
            ->firstOrFail();

        return new Resource($item);
    }

    public function show(string $ulid): Resource
    {
        $item = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(Requisition::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($item);
    }
}
```

**Key Points:**
- Controllers are lightweight - delegate business logic to Jobs
- Use `Spatie\QueryBuilder\QueryBuilder` for filtering/includes
- Use `dispatchSync()` to run Jobs synchronously
- Return API Resources for all responses
- Look up by ULID, not ID

---

## 3. Form Requests

**Location:** `app/Http/Requests/{Domain}/`

**Naming:** `CreateRequest.php`, `UpdateRequest.php`, `ApproveRequest.php`, etc.

**Pattern:**
```php
namespace App\Http\Requests\Requisition;

use App\Rules\Requisition\ApproveOnce;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'accounting_event_ulid' => ['required', 'string', 'exists:accounting_events,ulid'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'items' => ['sometimes', 'array'],
            'items.*.description' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric'],
        ];
    }
}
```

**Key Points:**
- Organized by domain in subdirectories
- Use `exists:table,column` for ULID validation
- Custom rules in `app/Rules/{Domain}/`
- Array validation for nested items

---

## 4. Jobs (Business Logic)

**Location:** `app/Jobs/{Domain}/`

**Naming:** `CreateJob.php`, `UpdateJob.php`, `ApproveJob.php`, etc.

**Pattern:**
```php
namespace App\Jobs\Requisition;

use App\Models\AccountingEvent;
use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data
    ) {}

    public function handle(): Requisition
    {
        // Convert ULIDs to IDs for foreign keys
        $accountingEvent = AccountingEvent::where('ulid', $this->data['accounting_event_ulid'])->firstOrFail();
        $requestedBy = Member::where('ulid', $this->data['requested_by_ulid'])->firstOrFail();

        $requisition = Requisition::create([
            'accounting_event_id' => $accountingEvent->id,
            'requested_by_id' => $requestedBy->id,
            'description' => $this->data['description'],
            'amount' => $this->data['amount'],
        ]);

        // Create related items if provided
        if (isset($this->data['items'])) {
            foreach ($this->data['items'] as $item) {
                $requisition->items()->create($item);
            }
        }

        return $requisition;
    }
}
```

**Key Points:**
- ALL business logic goes in Jobs
- Use constructor property promotion
- Convert ULIDs to IDs for database relationships
- Return the created/updated model
- Can dispatch other jobs for follow-up actions

---

## 5. Models

**Location:** `app/Models/`

**Pattern:**
```php
namespace App\Models;

use App\Enums\PRFApprovalStatus;
use App\Observers\RequisitionObserver;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

#[ObservedBy(RequisitionObserver::class)]
class Requisition extends Model
{
    use HasFactory, HasUlid, LogsActivity, SoftDeletes;

    // Define allowed includes for QueryBuilder
    public const INCLUDES = [
        'accountingEvent',
        'requestedBy',
        'items',
    ];

    protected $fillable = [
        'accounting_event_id',
        'requested_by_id',
        'description',
        'amount',
        'approval_status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'approval_status' => PRFApprovalStatus::class,
        ];
    }

    // Relationships with return type hints
    public function accountingEvent(): BelongsTo
    {
        return $this->belongsTo(AccountingEvent::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'requested_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }

    // Activity log configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
}
```

**Key Points:**
- Use `HasUlid` trait for automatic ULID generation
- Define `INCLUDES` constant for QueryBuilder eager loading
- Use `casts()` method (not `$casts` property)
- Use return type hints on relationships
- Register Observers with `#[ObservedBy()]` attribute
- Use `LogsActivity` trait for audit logging

---

## 6. API Resources

**Location:** `app/Http/Resources/{Domain}/Resource.php`

**Pattern:**
```php
namespace App\Http\Resources\Requisition;

use App\Http\Resources\AccountingEvent\Resource as AccountingEventResource;
use App\Http\Resources\Member\Resource as MemberResource;
use App\Http\Resources\RequisitionItem\Resource as ItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'requisition',
            'ulid' => $this->ulid,
            'description' => $this->description,
            'amount' => $this->amount,
            'approval_status' => $this->approval_status?->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships - use whenLoaded() to prevent N+1
            'accounting_event' => new AccountingEventResource($this->whenLoaded('accountingEvent')),
            'requested_by' => new MemberResource($this->whenLoaded('requestedBy')),
            'items' => ItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
```

**Key Points:**
- Each domain has its own `Resource.php` file
- Always include `'entity' => 'resource_name'` field
- Expose ULID, not ID
- Use `whenLoaded()` for relationships
- Use `->value` for enum values

---

## 7. Observers

**Location:** `app/Observers/`

**Pattern:**
```php
namespace App\Observers;

use App\Models\Requisition;
use App\Notifications\RequisitionRecalledNotification;
use Illuminate\Support\Facades\Notification;

class RequisitionObserver
{
    public function updated(Requisition $requisition): void
    {
        if ($requisition->wasChanged('approval_status')) {
            // Handle status change side effects
            if ($requisition->approval_status === PRFApprovalStatus::RECALLED) {
                $requisition->allocationEntries()->delete();
                Notification::send($recipients, new RequisitionRecalledNotification($requisition));
            }
        }
    }
}
```

**Key Points:**
- Handle model lifecycle side effects
- Register with `#[ObservedBy()]` attribute on model
- Use `wasChanged()` to detect specific field changes

---

## 8. Enums

**Location:** `app/Enums/`

**Pattern:**
```php
namespace App\Enums;

enum PRFApprovalStatus: int
{
    case PENDING = 0;
    case APPROVED = 1;
    case REJECTED = 2;
    case RECALLED = 3;

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->getLabel(),
        ])->toArray();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::RECALLED => 'Recalled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::RECALLED => 'gray',
        };
    }
}
```

**Key Points:**
- Integer-backed enums for database storage
- Include helper methods: `getValues()`, `getOptions()`, `getLabel()`, `getColor()`
- Keys are SCREAMING_CASE (e.g., `PENDING`, `APPROVED`)

---

## 9. Services

**Location:** `app/Services/`

Use for shared business logic that doesn't fit in a single Job.

```php
namespace App\Services;

class MissionCompletionService
{
    public function getCompletionChecklist(Mission $mission): array
    {
        return [
            'can_complete' => $this->canComplete($mission),
            'checks' => [
                'has_photos' => $mission->getMedia('photos')->isNotEmpty(),
                'has_notes' => filled($mission->notes),
                // ...
            ],
        ];
    }
}
```

---

## 10. Factories

**Location:** `database/factories/`

**Pattern:**
```php
namespace Database\Factories;

use App\Enums\PRFApprovalStatus;
use App\Models\AccountingEvent;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequisitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'accounting_event_id' => AccountingEvent::query()->inRandomOrder()->first()?->getKey(),
            'requested_by_id' => Member::query()->inRandomOrder()->first()?->getKey(),
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->numberBetween(1000, 50000),
            'approval_status' => $this->faker->randomElement(PRFApprovalStatus::getValues()),
        ];
    }
}
```

---

## 11. Custom Validation Rules

**Location:** `app/Rules/{Domain}/`

**Pattern:**
```php
namespace App\Rules\Requisition;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ApproveOnce implements ValidationRule
{
    public function __construct(
        protected string $ulid
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $requisition = Requisition::where('ulid', $this->ulid)->first();

        if ($requisition?->approval_status === PRFApprovalStatus::APPROVED) {
            $fail('This requisition has already been approved.');
        }
    }
}
```

---

## Directory Structure Summary

```
app/
├── Enums/                    # Integer-backed enums with helper methods
├── Helpers/                  # Utility functions (Utils class)
├── Http/
│   ├── Controllers/API/      # Lightweight controllers
│   ├── Requests/{Domain}/    # Form Request validation
│   └── Resources/{Domain}/   # API Resources (Resource.php)
├── Jobs/{Domain}/            # Business logic (CreateJob, UpdateJob, etc.)
├── Models/                   # Eloquent models (flat structure)
├── Observers/                # Model lifecycle hooks
├── Policies/                 # Authorization policies
├── Rules/{Domain}/           # Custom validation rules
├── Services/                 # Shared business logic
├── Traits/                   # HasUlid trait
├── Events/                   # Domain events
├── Listeners/                # Event listeners
└── Notifications/            # Notification classes

database/
├── factories/                # Model factories
├── migrations/               # Database migrations
└── seeders/                  # Database seeders

routes/
├── api/
│   ├── v1.php               # API v1 routes
│   └── v2.php               # API v2 routes
└── web.php                  # Web routes
```

---

## Key Libraries Used

- **Spatie QueryBuilder** - API filtering, sorting, includes
- **Spatie Activity Log** - Audit logging
- **Spatie Media Library** - File uploads
- **Spatie Permissions** - Roles and permissions
- **Laravel Sanctum** - API authentication
- **Filament** - Admin panel

---

## Checklist for New Features

1. [ ] Create migration for new table
2. [ ] Create Model with `HasUlid`, `HasFactory`, `LogsActivity` traits
3. [ ] Define `INCLUDES` constant on model
4. [ ] Create Factory in `database/factories/`
5. [ ] Create API Resource in `app/Http/Resources/{Domain}/Resource.php`
6. [ ] Create Form Requests in `app/Http/Requests/{Domain}/`
7. [ ] Create Jobs in `app/Jobs/{Domain}/` for business logic
8. [ ] Create Controller in `app/Http/Controllers/API/`
9. [ ] Add routes in `routes/api/v1.php`
10. [ ] Create Observer if needed for side effects
11. [ ] Write tests in `tests/Feature/`

# PRF SuperApp API - Architecture Guide

This section describes the code architecture and patterns used in the PRF Laravel API so AI agents can generate code that matches the project format.

---

## Request Flow Overview

```
HTTP Request
    ↓
Route (routes/api/v1.php or v2.php)
    ↓
Controller (app/Http/Controllers/API/)
    ↓
Form Request (app/Http/Requests/) → Validation + Authorization
    ↓
Job::dispatchSync() (app/Jobs/) → Business Logic
    ↓
Model Operations → Observers may trigger side effects
    ↓
API Resource (app/Http/Resources/) → JSON Response
```

---

## 1. Routes

**Location:** `routes/api/v1.php`, `routes/api/v2.php`

**Pattern:**
```php
Route::group([
    'prefix' => 'v1/missions',
    'middleware' => ['auth:sanctum'],
    'as' => 'api.missions.',
], function () {
    Route::get('/', [MissionController::class, 'index']);
    Route::post('/', [MissionController::class, 'store']);
    Route::get('/{ulid}', [MissionController::class, 'show']);
    Route::match(['put', 'patch'], '/{ulid}', [MissionController::class, 'update']);
    Route::delete('/{ulid}', [MissionController::class, 'destroy']);

    // Custom actions
    Route::post('/{ulid}/approve', [MissionController::class, 'approve']);
});
```

**Key Points:**
- Use `auth:sanctum` middleware for protected routes
- Use ULID string parameters (NOT implicit route model binding)
- Group routes by resource with prefix and named routes
- Custom actions use POST with descriptive names

---

## 2. Controllers

**Location:** `app/Http/Controllers/API/`

**Pattern:**
```php
class RequisitionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 100);
        $orderDirection = $request->get('order_direction', 'desc');
        $orderBy = $request->get('order_by', 'created_at');

        $items = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(Requisition::INCLUDES)
            ->allowedFilters([
                AllowedFilter::callback('status', fn ($query, $value) => ...),
            ])
            ->orderBy($orderBy, $orderDirection)
            ->simplePaginate($limit);

        return Resource::collection($items);
    }

    public function store(CreateRequest $request): Resource
    {
        $validated = $request->validated();
        $item = CreateJob::dispatchSync($validated);

        // Reload with eager loading
        $item = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(Requisition::INCLUDES)
            ->where('ulid', $item->ulid)
            ->firstOrFail();

        return new Resource($item);
    }

    public function show(string $ulid): Resource
    {
        $item = QueryBuilder::for(Requisition::class)
            ->allowedIncludes(Requisition::INCLUDES)
            ->where('ulid', $ulid)
            ->firstOrFail();

        return new Resource($item);
    }
}
```

**Key Points:**
- Controllers are lightweight - delegate business logic to Jobs
- Use `Spatie\QueryBuilder\QueryBuilder` for filtering/includes
- Use `dispatchSync()` to run Jobs synchronously
- Return API Resources for all responses
- Look up by ULID, not ID

---

## 3. Form Requests

**Location:** `app/Http/Requests/{Domain}/`

**Naming:** `CreateRequest.php`, `UpdateRequest.php`, `ApproveRequest.php`, etc.

**Pattern:**
```php
namespace App\Http\Requests\Requisition;

use App\Rules\Requisition\ApproveOnce;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'accounting_event_ulid' => ['required', 'string', 'exists:accounting_events,ulid'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'items' => ['sometimes', 'array'],
            'items.*.description' => ['required', 'string'],
            'items.*.amount' => ['required', 'numeric'],
        ];
    }
}
```

**Key Points:**
- Organized by domain in subdirectories
- Use `exists:table,column` for ULID validation
- Custom rules in `app/Rules/{Domain}/`
- Array validation for nested items

---

## 4. Jobs (Business Logic)

**Location:** `app/Jobs/{Domain}/`

**Naming:** `CreateJob.php`, `UpdateJob.php`, `ApproveJob.php`, etc.

**Pattern:**
```php
namespace App\Jobs\Requisition;

use App\Models\AccountingEvent;
use App\Models\Member;
use App\Models\Requisition;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data
    ) {}

    public function handle(): Requisition
    {
        // Convert ULIDs to IDs for foreign keys
        $accountingEvent = AccountingEvent::where('ulid', $this->data['accounting_event_ulid'])->firstOrFail();
        $requestedBy = Member::where('ulid', $this->data['requested_by_ulid'])->firstOrFail();

        $requisition = Requisition::create([
            'accounting_event_id' => $accountingEvent->id,
            'requested_by_id' => $requestedBy->id,
            'description' => $this->data['description'],
            'amount' => $this->data['amount'],
        ]);

        // Create related items if provided
        if (isset($this->data['items'])) {
            foreach ($this->data['items'] as $item) {
                $requisition->items()->create($item);
            }
        }

        return $requisition;
    }
}
```

**Key Points:**
- ALL business logic goes in Jobs
- Use constructor property promotion
- Convert ULIDs to IDs for database relationships
- Return the created/updated model
- Can dispatch other jobs for follow-up actions

---

## 5. Models

**Location:** `app/Models/`

**Pattern:**
```php
namespace App\Models;

use App\Enums\PRFApprovalStatus;
use App\Observers\RequisitionObserver;
use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

#[ObservedBy(RequisitionObserver::class)]
class Requisition extends Model
{
    use HasFactory, HasUlid, LogsActivity, SoftDeletes;

    // Define allowed includes for QueryBuilder
    public const INCLUDES = [
        'accountingEvent',
        'requestedBy',
        'items',
    ];

    protected $fillable = [
        'accounting_event_id',
        'requested_by_id',
        'description',
        'amount',
        'approval_status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'approval_status' => PRFApprovalStatus::class,
        ];
    }

    // Relationships with return type hints
    public function accountingEvent(): BelongsTo
    {
        return $this->belongsTo(AccountingEvent::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'requested_by_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequisitionItem::class);
    }

    // Activity log configuration
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }
}
```

**Key Points:**
- Use `HasUlid` trait for automatic ULID generation
- Define `INCLUDES` constant for QueryBuilder eager loading
- Use `casts()` method (not `$casts` property)
- Use return type hints on relationships
- Register Observers with `#[ObservedBy()]` attribute
- Use `LogsActivity` trait for audit logging

---

## 6. API Resources

**Location:** `app/Http/Resources/{Domain}/Resource.php`

**Pattern:**
```php
namespace App\Http\Resources\Requisition;

use App\Http\Resources\AccountingEvent\Resource as AccountingEventResource;
use App\Http\Resources\Member\Resource as MemberResource;
use App\Http\Resources\RequisitionItem\Resource as ItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'entity' => 'requisition',
            'ulid' => $this->ulid,
            'description' => $this->description,
            'amount' => $this->amount,
            'approval_status' => $this->approval_status?->value,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships - use whenLoaded() to prevent N+1
            'accounting_event' => new AccountingEventResource($this->whenLoaded('accountingEvent')),
            'requested_by' => new MemberResource($this->whenLoaded('requestedBy')),
            'items' => ItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
```

**Key Points:**
- Each domain has its own `Resource.php` file
- Always include `'entity' => 'resource_name'` field
- Expose ULID, not ID
- Use `whenLoaded()` for relationships
- Use `->value` for enum values

---

## 7. Observers

**Location:** `app/Observers/`

**Pattern:**
```php
namespace App\Observers;

use App\Models\Requisition;
use App\Notifications\RequisitionRecalledNotification;
use Illuminate\Support\Facades\Notification;

class RequisitionObserver
{
    public function updated(Requisition $requisition): void
    {
        if ($requisition->wasChanged('approval_status')) {
            // Handle status change side effects
            if ($requisition->approval_status === PRFApprovalStatus::RECALLED) {
                $requisition->allocationEntries()->delete();
                Notification::send($recipients, new RequisitionRecalledNotification($requisition));
            }
        }
    }
}
```

**Key Points:**
- Handle model lifecycle side effects
- Register with `#[ObservedBy()]` attribute on model
- Use `wasChanged()` to detect specific field changes

---

## 8. Enums

**Location:** `app/Enums/`

**Pattern:**
```php
namespace App\Enums;

enum PRFApprovalStatus: int
{
    case PENDING = 0;
    case APPROVED = 1;
    case REJECTED = 2;
    case RECALLED = 3;

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function getOptions(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->getLabel(),
        ])->toArray();
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
            self::RECALLED => 'Recalled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
            self::RECALLED => 'gray',
        };
    }
}
```

**Key Points:**
- Integer-backed enums for database storage
- Include helper methods: `getValues()`, `getOptions()`, `getLabel()`, `getColor()`
- Keys are SCREAMING_CASE (e.g., `PENDING`, `APPROVED`)

---

## 9. Services

**Location:** `app/Services/`

Use for shared business logic that doesn't fit in a single Job.

```php
namespace App\Services;

class MissionCompletionService
{
    public function getCompletionChecklist(Mission $mission): array
    {
        return [
            'can_complete' => $this->canComplete($mission),
            'checks' => [
                'has_photos' => $mission->getMedia('photos')->isNotEmpty(),
                'has_notes' => filled($mission->notes),
                // ...
            ],
        ];
    }
}
```

---

## 10. Factories

**Location:** `database/factories/`

**Pattern:**
```php
namespace Database\Factories;

use App\Enums\PRFApprovalStatus;
use App\Models\AccountingEvent;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

class RequisitionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'accounting_event_id' => AccountingEvent::query()->inRandomOrder()->first()?->getKey(),
            'requested_by_id' => Member::query()->inRandomOrder()->first()?->getKey(),
            'description' => $this->faker->sentence(),
            'amount' => $this->faker->numberBetween(1000, 50000),
            'approval_status' => $this->faker->randomElement(PRFApprovalStatus::getValues()),
        ];
    }
}
```

---

## 11. Custom Validation Rules

**Location:** `app/Rules/{Domain}/`

**Pattern:**
```php
namespace App\Rules\Requisition;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ApproveOnce implements ValidationRule
{
    public function __construct(
        protected string $ulid
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $requisition = Requisition::where('ulid', $this->ulid)->first();

        if ($requisition?->approval_status === PRFApprovalStatus::APPROVED) {
            $fail('This requisition has already been approved.');
        }
    }
}
```

---

## Directory Structure Summary

```
app/
├── Enums/                    # Integer-backed enums with helper methods
├── Helpers/                  # Utility functions (Utils class)
├── Http/
│   ├── Controllers/API/      # Lightweight controllers
│   ├── Requests/{Domain}/    # Form Request validation
│   └── Resources/{Domain}/   # API Resources (Resource.php)
├── Jobs/{Domain}/            # Business logic (CreateJob, UpdateJob, etc.)
├── Models/                   # Eloquent models (flat structure)
├── Observers/                # Model lifecycle hooks
├── Policies/                 # Authorization policies
├── Rules/{Domain}/           # Custom validation rules
├── Services/                 # Shared business logic
├── Traits/                   # HasUlid trait
├── Events/                   # Domain events
├── Listeners/                # Event listeners
└── Notifications/            # Notification classes

database/
├── factories/                # Model factories
├── migrations/               # Database migrations
└── seeders/                  # Database seeders

routes/
├── api/
│   ├── v1.php               # API v1 routes
│   └── v2.php               # API v2 routes
└── web.php                  # Web routes
```

---

## Key Libraries Used

- **Spatie QueryBuilder** - API filtering, sorting, includes
- **Spatie Activity Log** - Audit logging
- **Spatie Media Library** - File uploads
- **Spatie Permissions** - Roles and permissions
- **Laravel Sanctum** - API authentication
- **Filament** - Admin panel

---

## Checklist for New Features

1. [ ] Create migration for new table
2. [ ] Create Model with `HasUlid`, `HasFactory`, `LogsActivity` traits
3. [ ] Define `INCLUDES` constant on model
4. [ ] Create Factory in `database/factories/`
5. [ ] Create API Resource in `app/Http/Resources/{Domain}/Resource.php`
6. [ ] Create Form Requests in `app/Http/Requests/{Domain}/`
7. [ ] Create Jobs in `app/Jobs/{Domain}/` for business logic
8. [ ] Create Controller in `app/Http/Controllers/API/`
9. [ ] Add routes in `routes/api/v1.php`
10. [ ] Create Observer if needed for side effects
11. [ ] Write tests in `tests/Feature/`
