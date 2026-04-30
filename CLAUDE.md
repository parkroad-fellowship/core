<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5.2
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
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `bun run build`, `bun run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `bun run build` or ask the user to run `bun run dev` or `composer run dev`.

=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version-specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== livewire/core rules ===

## Livewire

- Use the `search-docs` tool to find exact version-specific documentation for how to write Livewire and Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` Artisan command to create new components.
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend; they're like regular HTTP requests. Always validate form data and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle Hook Examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>

## Testing Livewire

<code-snippet name="Example Livewire Component Test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>

<code-snippet name="Testing Livewire Component Exists on Page" lang="php">
    $this->get('/posts/create')
    ->assertSeeLivewire(CreatePost::class);
</code-snippet>

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests that have a lot of duplicated data. This is often the case when testing validation rules, so consider this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>

=== pest/v4 rules ===

## Pest 4

- Pest 4 is a huge upgrade to Pest and offers: browser testing, smoke testing, visual regression testing, test sharding, and faster type coverage.
- Browser testing is incredibly powerful and useful for this project.
- Browser tests should live in `tests/Browser/`.
- Use the `search-docs` tool for detailed guidance on utilizing these features.

### Browser Testing
- You can use Laravel features like `Event::fake()`, `assertAuthenticated()`, and model factories within Pest 4 browser tests, as well as `RefreshDatabase` (when needed) to ensure a clean state for each test.
- Interact with the page (click, type, scroll, select, submit, drag-and-drop, touch gestures, etc.) when appropriate to complete the test.
- If requested, test on multiple browsers (Chrome, Firefox, Safari).
- If requested, test on different devices and viewports (like iPhone 14 Pro, tablets, or custom breakpoints).
- Switch color schemes (light/dark mode) when appropriate.
- Take screenshots or pause tests for debugging when appropriate.

### Example Tests

<code-snippet name="Pest Browser Test Example" lang="php">
it('may reset the password', function () {
    Notification::fake();

    $this->actingAs(User::factory()->create());

    $page = visit('/sign-in'); // Visit on a real browser...

    $page->assertSee('Sign In')
        ->assertNoJavascriptErrors() // or ->assertNoConsoleLogs()
        ->click('Forgot Password?')
        ->fill('email', 'nuno@laravel.com')
        ->click('Send Reset Link')
        ->assertSee('We have emailed your password reset link!')

    Notification::assertSent(ResetPassword::class);
});
</code-snippet>

<code-snippet name="Pest Smoke Testing Example" lang="php">
$pages = visit(['/', '/about', '/contact']);

$pages->assertNoJavascriptErrors()->assertNoConsoleLogs();
</code-snippet>

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind CSS 4

- Always use Tailwind CSS v4; do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.

<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>

### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option; use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |

=== filament/filament rules ===

## Filament

- Filament is used by this application. Follow existing conventions for how and where it's implemented.
- Filament is a Server-Driven UI (SDUI) framework for Laravel that lets you define user interfaces in PHP using structured configuration objects. Built on Livewire, Alpine.js, and Tailwind CSS.
- Use the `search-docs` tool for official documentation on Artisan commands, code examples, testing, relationships, and idiomatic practices.

### Artisan

- Use Filament-specific Artisan commands to create files. Find them with `list-artisan-commands` or `php artisan --help`.
- Inspect required options and always pass `--no-interaction`.

### Patterns

Use static `make()` methods to initialize components. Most configuration methods accept a `Closure` for dynamic values.

Use `Get $get` to read other form field values for conditional logic:

<code-snippet name="Conditional form field" lang="php">
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

Use `state()` with a `Closure` to compute derived column values:

<code-snippet name="Computed table column" lang="php">
use Filament\Tables\Columns\TextColumn;

TextColumn::make('full_name')
    ->state(fn (User $record): string => "{$record->first_name} {$record->last_name}"),
</code-snippet>

Actions encapsulate a button with optional modal form and logic:

<code-snippet name="Action with modal form" lang="php">
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

Action::make('updateEmail')
    ->form([
        TextInput::make('email')->email()->required(),
    ])
    ->action(fn (array $data, User $record): void => $record->update($data)),
</code-snippet>

### Testing

Authenticate before testing panel functionality. Filament uses Livewire, so use `livewire()` or `Livewire::test()`:

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Test',
            'email' => 'test@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Test',
        'email' => 'test@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Validation" lang="php">
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

<code-snippet name="Calling Actions" lang="php">
    use Filament\Actions\DeleteAction;
    use Filament\Actions\Testing\TestAction;

    livewire(EditUser::class, ['record' => $user->id])
        ->callAction(DeleteAction::class)
        ->assertNotified()
        ->assertRedirect();

    livewire(ListUsers::class)
        ->callAction(TestAction::make('promote')->table($user), [
            'role' => 'admin',
        ])
        ->assertNotified();
</code-snippet>

### Common Mistakes

**Commonly Incorrect Namespaces:**
- Form fields (TextInput, Select, etc.): `Filament\Forms\Components\`
- Infolist entries (for read-only views) (TextEntry, IconEntry, etc.): `Filament\Infolists\Components\`
- Layout components (Grid, Section, Fieldset, Tabs, Wizard, etc.): `Filament\Schemas\Components\`
- Schema utilities (Get, Set, etc.): `Filament\Schemas\Components\Utilities\`
- Actions: `Filament\Actions\` (no `Filament\Tables\Actions\` etc.)
- Icons: `Filament\Support\Icons\Heroicon` enum (e.g., `Heroicon::PencilSquare`)

**Recent breaking changes to Filament:**
- File visibility is `private` by default. Use `->visibility('public')` for public access.
- `Grid`, `Section`, and `Fieldset` no longer span all columns by default.
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
