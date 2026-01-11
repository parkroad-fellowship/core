# Parkroad Fellowship (PRF) Core System

## Project Overview

This is a comprehensive Laravel 12 application for managing evangelistic missions to secondary schools and institutions across Kenya. The system handles mission planning, resource allocation, team deployment, follow-up activities, and reporting.

### Key Features
- **Mission Management**: Plan and execute missions to schools with comprehensive tracking
- **School Management**: Database of schools with location, contacts, and history
- **Team Management**: Member subscriptions, role assignments, and attendance tracking
- **Financial Tracking**: Budget planning, expense management, and reporting
- **Cohort Management**: Student training groups and follow-up programs
- **Event Management**: Schedule events with weather-based recommendations
- **Reporting**: Executive summaries, teacher feedback, and analytics
- **Mobile Apps**: Android, iOS, and Huawei app integration for field teams

### Tech Stack
- **Backend**: Laravel 12 (PHP 8.4)
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS 4
- **Admin Panel**: Filament 3 (Server-Driven UI framework)
- **Testing**: Pest with PHPUnit
- **Database**: PostgreSQL (with Eloquent ORM)
- **External Services**: 
  - Google Maps API (location services)
  - Google Gemini AI (recommendations and summaries)
  - Firebase (push notifications)
  - Africa's Talking (SMS)
  - Azure Blob Storage

### Domain Concepts

#### Missions
The core entity representing an evangelistic outreach to a school. Each mission has:
- Mission type (e.g., evangelism, training)
- School and school term association
- Start/end dates and times
- Theme and capacity
- Team subscriptions
- Financial tracking
- Weather-based recommendations
- Executive summary (AI-generated)

#### Schools
Educational institutions where missions take place:
- Institution types (high school, primary school, university, etc.)
- Location data (with Google Maps integration)
- Contact information
- Active/inactive status
- Historical mission data

#### Cohorts
Student training groups for follow-up after missions:
- Title and start date
- Active/inactive status
- Student tracking
- Training schedules

#### Members
Fellowship members who participate in missions with specific roles:
- Leaders, trainers, music team, transportation
- Subscription status and attendance tracking
- Role-based permissions

#### Financial Management
- Mission budgets and expenses
- Receipt tracking with media library
- Budget efficiency calculations
- Accounting events

### Business Rules

1. **Permissions**: Role-based access using Spatie Laravel Permission
2. **Active Status**: Schools and cohorts can be active/inactive
3. **Executive Committee**: Specific roles with special access
4. **Excluded Emails**: System accounts that shouldn't appear in member lists
5. **Offline Members**: Members without user accounts can be tracked
6. **Weather Recommendations**: AI-generated based on forecast data
7. **Notifications**: Event handlers manage who receives notifications

### Code Organization

- **Models**: Eloquent models in `app/Models/`
- **Filament Resources**: CRUD interfaces in `app/Filament/Resources/`
- **Jobs**: Background tasks in `app/Jobs/` (e.g., `GenerateExecutiveSummaryJob`)
- **Enums**: Type-safe enumerations in `app/Enums/` (e.g., `PRFActiveStatus`, `PRFInstitutionType`)
- **Helpers**: Utility functions in `app/Helpers/`
- **Policies**: Authorization logic in `app/Policies/`

### Key Conventions

1. **Emoji Usage**: UI elements use emojis for visual clarity (🏫, 📍, 👥, etc.)
2. **Helper Functions**: Custom helper `userCan()` for permission checks
3. **Configuration**: PRF-specific config in `config/prf/app.php`
4. **Relationships**: Heavy use of Eloquent relationships with proper type hints
5. **Factory States**: Models have factories with custom states for testing
6. **AI Integration**: Gemini API for generating summaries and recommendations

### Important Patterns

1. **Filament Forms**: Extensive use of sections, grids, and field groups with icons and descriptions
2. **Location Services**: Geocomplete fields with Google Maps for school locations
3. **Media Library**: Spatie Media Library for file uploads (receipts, images)
4. **Activity Logging**: Spatie Activity Log for audit trails
5. **API Resources**: Version-specific API endpoints for mobile apps
6. **Queue Jobs**: Long-running tasks (AI generation, PDF creation) are queued

<laravel-boost-guidelines>
=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== filament/core rules ===

## Filament
- Filament is used by this application, check how and where to follow existing application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.

### Artisan
- You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features
- Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships
- Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>


### Testing
- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
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
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

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

- Prefer lifecycle hooks like `mount()`, `updatedFoo()`) for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
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
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
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
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
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
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

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

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff"
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
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


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.


=== prf-app rules ===

## Park Road Fellowship Application-Specific Rules

### Configuration

- **PRF Config**: Use `config('prf.app.*')` for application-specific settings
- **Excluded Emails**: System emails defined in `config('prf.app.excluded_emails')` should not appear in member dropdowns
- **Executive Committee**: Roles defined in `config('prf.app.executive_committee.roles')`
- **Gemini API**: Configuration at `config('prf.app.gemini')`
- **Google Maps**: API key at `config('prf.app.google_maps.api_key')`

### Models and Relationships

- **Mission Model**: Central entity with relationships to School, SchoolTerm, MissionType, MissionSubscriptions, Souls, etc.
- **Always eager load**: Use the `INCLUDES` constant on models for common relationships
- **Offline Members**: The `offline_members` field stores array of members without user accounts
- **Casts**: Use the `casts()` method on models, not the `$casts` property (Laravel 12 convention)

### Enums

- Use type-safe enums from `app/Enums/` directory
- Common enums: `PRFActiveStatus`, `PRFInstitutionType`
- Enums should have `getOptions()` method for Filament select fields
- Example: `PRFActiveStatus::ACTIVE->value` for database values

### Filament Resources

- **Icons**: Use Heroicons with descriptive labels (e.g., `heroicon-o-academic-cap`)
- **Emoji Labels**: Include emojis in labels for better UX (e.g., "🏫 School Name")
- **Helper Text**: Always provide helpful descriptions for form fields
- **Sections**: Group related fields with icons and descriptions
- **Collapsible**: Use `collapsible()` and `persistCollapsed()` for long forms
- **Native Selects**: Set `native(false)` for better UX on mobile
- **Permissions**: Check permissions using `userCan()` helper function

### AI Integration (Gemini)

- **Executive Summaries**: Generated by `GenerateExecutiveSummaryJob`
- **Weather Recommendations**: AI-generated dressing, activity, and weather recommendations
- **System Prompts**: Include comprehensive context about PRF's mission and goals
- **Timeout**: Set appropriate timeouts for AI API calls (e.g., 240 seconds)

### Location Services

- **Geocomplete**: Use `FilamentGoogleMaps\Fields\Geocomplete` for location search
- **Map Component**: Use `FilamentGoogleMaps\Fields\Map` for displaying locations
- **Default Location**: Nairobi coordinates `[-1.319167, 36.9275]`
- **Distance Calculation**: Automatically calculated from headquarters

### Testing Conventions

- **Factories**: Use model factories with custom states for test data
- **Authentication**: Always authenticate before testing protected routes
- **Pest Syntax**: Use `it()` and `expect()` for readable tests
- **Database Assertions**: Use `assertDatabaseHas()` for checking data

### Notifications and Events

- **FCM**: Firebase Cloud Messaging for mobile push notifications
- **Event Handlers**: Configured per event for subscription notifications
- **Email Lists**: Use configured desk email lists (e.g., `organising_secretary_desk.emails`)

### Mobile Apps

- **Platform URLs**: Defined in `config('prf.app.app_stores')` for Android, iOS, Huawei
- **API Versioning**: Use versioned API resources for mobile app compatibility
- **Sanctum**: API authentication using Laravel Sanctum

### Common Patterns to Follow

1. **Permission Checks**: Always use `userCan('permission name')` in Filament actions/resources
2. **Media Uploads**: Use Spatie Media Library with appropriate collections
3. **Activity Logging**: Automatic logging on important model changes
4. **Soft Deletes**: Most models use soft deletes for data integrity
5. **Timestamps**: All models track created_at and updated_at
6. **User Timezone**: Respect user timezone in date/time displays
7. **Query Builder**: Use Spatie Query Builder for complex API queries
8. **Excel Exports**: Use Maatwebsite Excel for data exports

### UI/UX Guidelines

1. **Consistent Icons**: Use consistent emoji and Heroicon patterns across resources
2. **Helper Text**: Provide clear, actionable helper text for all form fields
3. **Placeholders**: Include realistic examples in field placeholders
4. **Validation Messages**: Clear, user-friendly error messages
5. **Tooltips**: Add tooltips to table actions for clarity
6. **Color Coding**: Use Filament's color system consistently
7. **Responsive Design**: Ensure mobile compatibility with Tailwind utilities

### Performance Considerations

1. **N+1 Queries**: Always eager load relationships using `with()`
2. **Queue Long Tasks**: AI generation, PDF creation, email sending
3. **Indexing**: Ensure frequently queried fields are indexed
4. **Pagination**: Use pagination for large datasets
5. **Caching**: Consider caching for expensive queries

### Security Practices

1. **CSRF Protection**: Enabled by default on all forms
2. **Authorization**: Use policies and gates for all sensitive operations
3. **Mass Assignment**: Use `$fillable` or `$guarded` on all models
4. **SQL Injection**: Always use Eloquent or query builder, never raw SQL
5. **XSS Prevention**: Blade templates escape output by default
6. **API Rate Limiting**: Configured in routing

</laravel-boost-guidelines>
