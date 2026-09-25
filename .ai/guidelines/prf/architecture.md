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

`app/Http/Controllers/Controller.php` implements `index`, `show` and `destroy`. A resource controller sets two properties and overrides only `store`, `update` and its custom actions:

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

## Routes

- New v1 resources go inside the existing protected group in `routes/api/v1.php`, which already applies `tenant.initialized`, `auth:sanctum` and `tenant.validate`. Add a group with `'prefix' => 'v1/{kebab-plural}'` and `'as' => 'api.{kebab-plural}.'`.
- Use `{ulid}` parameters. Updates use `Route::match(['put', 'patch'], '/{ulid}', …)`. Custom actions are `POST /{ulid}/{verb}`.
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
