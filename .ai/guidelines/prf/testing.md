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
- **Side effects:**
  - In job tests: `Event::fake([...])`, then `Event::assertDispatched(...)`.
  - In listener tests: `Notification::fake()` or `Queue::fake()`.
  - After-commit events still run in tests (`RefreshDatabase`).
- Every change ships with a test. Run only the affected file while working, then run `make test` before finishing.
