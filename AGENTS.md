<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- filament/filament (FILAMENT) - v5
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/pulse (PULSE) - v1
- laravel/reverb (REVERB) - v1
- laravel/sanctum (SANCTUM) - v4
- laravel/socialite (SOCIALITE) - v5
- laravel/telescope (TELESCOPE) - v5
- livewire/livewire (LIVEWIRE) - v4
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4

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

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

### Enum Columns

- Integer-backed enum columns MUST be cast in the model via a `casts()` method (never a `$casts` property): `'status' => PRFMissionStatus::class`. The model is the single source of truth for both the app-side type and the wire format.
- Model attributes return enum instances. Compare against cases directly (`$mission->status === PRFMissionStatus::SERVICED`) — NEVER call `Enum::from()/fromValue()` on a model attribute, and never compare attributes against `CASE->value`.
- Query builder wheres pass the case directly: `->where('status', PRFMissionStatus::SERVICED)`.
- API Resources emit these attributes RAW (`'status' => $this->status`); backed enums JSON-serialize to their backing int, so the wire format stays integer for mobile.

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

=== laravel/v12 rules ===

# Laravel 12

- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app/Console/Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

# Livewire

- Livewire allow to build dynamic, reactive interfaces in PHP without writing JavaScript.
- You can use Alpine.js for client-side interactions instead of JavaScript frameworks.
- Keep state server-side so the UI reflects it. Validate and authorize in actions as you would in HTTP requests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

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
