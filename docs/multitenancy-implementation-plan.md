# PRF Multitenancy — Itemized Implementation Plan

## Scope & Decisions

| Decision | Value |
|----------|-------|
| Database model | Single-database with `tenant_id` column on all tenant-owned tables |
| RLS defense-in-depth | Postgres Row-Level Security using `TableRLSManager` (stancl/tenancy v4) |
| API tenancy | Header `X-Tenant` via `InitializeTenancyByRequestData` middleware |
| Filament tenancy | Domain/subdomain via `InitializeTenancyByDomainOrSubdomain` middleware |
| Tenant identifier | ULID (26-char string) — sent as `X-Tenant` header value, stored as `tenant_id` string column |
| Tenant web identifier | Slug for subdomain routing. Custom domains also supported as additional `Domain` records |
| User model | **Global identity table** — single `users` table, no `tenant_id`. `users.email` globally unique. User can belong to many tenants via `tenant_user` pivot |
| Current tenant | Resolved from context (X-Tenant header for API, domain/subdomain for Filament). Never from user record |
| Sanctum tokens | **Tenant-bound** — minted in a tenant context, scoped to that tenant via `PersonalAccessToken.tenant_id`. Token from Tenant A cannot authenticate in Tenant B. Users need separate tokens per tenant context |
| Feature management | Per-tenant feature flags stored in `AppSetting` table (group=features), enforced in API/Filament/Jobs |
| Migration strategy | Backfill existing data into first tenant, then enforce `tenant_id NOT NULL` on tenant-owned tables only |
| Onboarding SLA | Internal subdomain provisioning < 5 minutes |

---

## Phase 0: Configuration & Foundation

### 0.1 Declare central domains and wildcard DNS

**Files**: `config/tenancy.php`, `.env.example`, `.env`

```php
'central_domains' => explode(',', env('TENANCY_CENTRAL_DOMAINS', 'prf.test,localhost')),
```

**.env.example**:
```
TENANCY_CENTRAL_DOMAINS=prf.test,localhost
```

Wildcard DNS `*.prf.example.org` must resolve.

### 0.2 Add RLS credentials to environment

**Files**: `.env.example`, `.env`

```
TENANCY_RLS_USERNAME=prf_rls
TENANCY_RLS_PASSWORD=
```

**One-time Postgres setup**:
```sql
CREATE ROLE prf_rls WITH NOINHERIT LOGIN PASSWORD '...';
GRANT USAGE ON SCHEMA public TO prf_rls;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO prf_rls;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO prf_rls;

ALTER DEFAULT PRIVILEGES IN SCHEMA public
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO prf_rls;

ALTER DEFAULT PRIVILEGES IN SCHEMA public
GRANT USAGE, SELECT ON SEQUENCES TO prf_rls;
```

### 0.3 Enable all required tenancy bootstrappers

**File**: `config/tenancy.php` — `'bootstrappers'` array

**Current**:
```php
Bootstrappers\FilesystemTenancyBootstrapper::class,
Bootstrappers\QueueTenancyBootstrapper::class,
```

**Change to**:
```php
Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
Stancl\Tenancy\Bootstrappers\PostgresRLSBootstrapper::class,
```

### 0.4 Disable database prefix/suffix for single-database RLS mode

**File**: `config/tenancy.php` — `'database'` section

```php
'prefix' => '',
'suffix' => '',
```

With `prefix = 'tenant'`, stancl/tenancy attempts database switching. For single-database + RLS, this must be empty.

Also explicitly set tenant IDs to ULID.

**File**: `config/tenancy.php` — top-level setting

```php
'id_generator' => Stancl\Tenancy\UniqueIdentifierGenerators\ULIDGenerator::class,
```

### 0.5 Add `tenant_id` foreign keys on all tenant-owned tables (execute in Phase 9 order)

**File**: Create `2026_07_09_000300_add_tenant_id_foreign_keys.php`

`TableRLSManager` generates policies by discovering `tenant_id` columns and following FK paths. FK constraints are required for reliable policy generation.

**Exclude these tables** from FK enforcement — they are NOT tenant-owned:
- `users` — global identity table, no `tenant_id`
- `tenants` — central registry
- `domains` — central, FK to `tenants.id`
- `tenant_user` — central membership pivot (excluded via `COMMENT ON COLUMN tenant_user.tenant_id IS 'no-rls'`)
- `jobs`, `cache`, `sessions` — Laravel internals
- `pulse_*`, `telescope_*` — monitoring

**All other tables with `tenant_id` get FK — including `personal_access_tokens`**. The existing backfill migration already added a nullable `tenant_id` column to all 84 tables, including `personal_access_tokens`. This migration adds FK constraints for referential integrity.

**RLS exclusion for `tenant_user`**: mark its tenant FK column with `no-rls` comment so `TableRLSManager` ignores that path.

```php
DB::statement("COMMENT ON COLUMN tenant_user.tenant_id IS 'no-rls'");
```

```php
$tables = [/* 84 tables with tenant_id column - exclude only system/central tables */];

foreach ($tables as $table) {
    if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
        Schema::table($table, function (Blueprint $table) {
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade');
        });
    }
}
```

### 0.6 Add tenancy variables to `.env.example`

```
TENANCY_CENTRAL_DOMAINS=prf.test,localhost
TENANCY_RLS_USERNAME=prf_rls
TENANCY_RLS_PASSWORD=
```

---

## Phase 1: User & Membership Model — Global Identity + Many-to-Many

### 1.1 Remove `users.tenant_id` column

**Migration**: `2026_07_09_000500_drop_tenant_id_from_users.php`

```php
DB::statement('DROP INDEX IF EXISTS users_tenant_id_index');
DB::statement('DROP INDEX IF EXISTS users_tenant_id_email_unique');
DB::statement('ALTER TABLE users DROP COLUMN IF EXISTS tenant_id');
```

**⚠️ Precondition**: `tenant_user` pivot must be backfilled (Phase 1.5) before this migration runs. All membership data moves to the pivot.

### 1.2 Enforce global uniqueness on `users.email`

**Migration**: Keep this in **Phase 9 only** after dedupe (do not enforce in Phase 1).

```php
// Do not run until duplicates are resolved.
DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users (email)');
```

### 1.3 Create `tenant_user` pivot table

**Migration**: `2026_07_09_000100_create_tenant_user_table.php`

```php
Schema::create('tenant_user', function (Blueprint $table) {
    $table->id();
    $table->string('tenant_id', 26);
    $table->unsignedBigInteger('user_id');
    $table->string('role')->nullable();
    $table->timestamps();

    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    $table->unique(['tenant_id', 'user_id']);
});
```

### 1.4 Update User model — no tenant_id, explicit pivot keys

**File**: `app/Models/User.php`

```php
class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasUlid;
    use Notifiable;
    use SoftDeletes;
    // NO BelongsToTenant — users are global

    /**
     * Tenants this user belongs to.
     * Pivot keys match default conventions (user_id → users.id, tenant_id → tenants.id).
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenant_user',
            'user_id',      // FK in pivot pointing to users
            'tenant_id',    // FK in pivot pointing to tenants
        )->withPivot('role')
         ->withTimestamps();
    }

    /**
     * Quick membership check against the pivot table.
     */
    public function belongsToTenant(string $tenantId): bool
    {
        return $this->tenants()->where('tenants.id', $tenantId)->exists();
    }

    /**
     * All tenant IDs this user belongs to, cached for the request.
     */
    public function getTenantIdsAttribute(): array
    {
        return $this->tenants()->pluck('tenants.id')->toArray();
    }
}
```

**⚠️ Pivot keys**: `BelongsToMany` defaults to `user_id` → `users.id` and `tenant_id` → `tenants.id`, both of which match our column names. The explicit `withPivot('role')` and `withTimestamps()` are required. `User`'s primary key is `id` (auto-increment integer), and `Tenant`'s primary key is `id` (ULID string from stancl).

### 1.5 Update Tenant model — explicit pivot keys

**File**: `app/Models/Tenant.php`

```php
protected $fillable = [
    'id',
    'name',
    'slug',
    'is_active',
    'data',
];

protected function casts(): array
{
    return [
        'is_active' => 'boolean',
        'data' => 'array',
    ];
}

public function members(): BelongsToMany
{
    return $this->belongsToMany(
        User::class,
        'tenant_user',
        'tenant_id',    // FK in pivot pointing to tenants
        'user_id',      // FK in pivot pointing to users
    )->withPivot('role')
     ->withTimestamps();
}

public function hasMember(User $user): bool
{
    return $this->members()->whereKey($user->getKey())->exists();
}
```

### 1.5.1 Ensure `slug` and `is_active` are real tenant columns

**Migration**: `2026_07_09_000200_add_slug_config_to_tenants_table.php`

```php
Schema::table('tenants', function (Blueprint $table) {
    $table->string('name')->nullable()->after('id');
    $table->string('slug')->unique()->after('name');
    $table->boolean('is_active')->default(true)->after('slug');
});
```

This avoids relying on `data` JSON for tenant lookup in commands like `Tenant::where('slug', ...)`.

### 1.6 Backfill existing users into `tenant_user` pivot

**Migration**: `2026_07_09_000400_backfill_tenant_user.php`

Run **before** dropping `users.tenant_id`.

```php
DB::statement("
    INSERT INTO tenant_user (tenant_id, user_id, role, created_at, updated_at)
    SELECT u.tenant_id, u.id, 'member', NOW(), NOW()
    FROM users u
    WHERE u.tenant_id IS NOT NULL
    ON CONFLICT (tenant_id, user_id) DO NOTHING
");
```

### 1.7 Update TenantObserver — minimal bootstrap only

**File**: `app/Observers/TenantObserver.php`

Observer does NOT run provisioning. It only sets up identity:

```php
class TenantObserver
{
    public function creating(Tenant $tenant): void
    {
        if (empty($tenant->slug)) {
            $baseSlug = Str::slug($tenant->name) ?: 'tenant';
            $slug = $baseSlug;
            $counter = 1;

            while (Tenant::query()->where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $tenant->slug = $slug;
        }
    }

    public function created(Tenant $tenant): void
    {
        // Auto-create the slug subdomain only
        $centralDomains = config('tenancy.identification.central_domains', []);
        $centralDomain = $centralDomains[0] ?? null;

        if (! $centralDomain) {
            Log::warning('Skipping default tenant domain creation: no central domain configured.', [
                'tenant_id' => $tenant->id,
            ]);
            return;
        }

        $tenant->domains()->firstOrCreate([
            'domain' => "{$tenant->slug}.{$centralDomain}",
        ]);
        // Full provisioning (seeders, admin user) is owned by tenants:create command.
    }
}
```

**Why separate?** The observer cannot know about `--admin-email` or `--features`. The command is the authoritative orchestration path.

### 1.8 Update TenantFactory

**File**: `database/factories/TenantFactory.php`

```php
public function definition(): array
{
    return [
        'name' => $this->faker->company(),
        'slug' => Str::slug($this->faker->unique()->company()),
        'is_active' => true,
    ];
}
```

### 1.9 Seed a reproducible default tenant

**File**: Create `database/seeders/TenantSeeder.php`

Seed the bootstrap tenant with a known ULID for migration backfill compatibility.

---

## Phase 2: API Authentication — Tenant-Bound Token Model

This phase documents the authentication flow end-to-end, which is the most critical design decision for a multi-tenant API with global user identity.

`InitializeTenancyByRequestData` is provided by stancl/tenancy v4 and resolves tenants from configured request data (`header` => `X-Tenant` in `config/tenancy.php`). No custom middleware is required for header extraction.

### 2.1 Authentication flow (explicit)

```
1. Client sends POST /api/v1/auth/login
    Headers: X-Tenant: <tenant-ulid>
   Body: { email, password }

2. Middleware (InitializeTenancyByRequestData):
   - Reads X-Tenant header
    - Resolves tenant ULID in the tenancy system
   - Initializes tenant context: DB queries now scope to this tenant
    - **At this point, `tenancy()->initialized = true`, `tenant('id')` returns ULID**

3. Controller (AuthController::login):
   - Looks up User globally (no tenant scope on User model)
   - Validates password
   - **Check: user must belong to this tenant via `tenant_user` pivot**
   - If not a member: 403 TENANT_USER_MISMATCH
   - If member: issue Sanctum token

4. Token creation:
   - `$user->createToken('token-name')` — Sanctum creates a `personal_access_tokens` record
   - `PersonalAccessToken` has `BelongsToTenant` trait
   - The token record is stamped with the **current tenant ID**
   - Token ability scopes can be applied (e.g., `['missions:read']`)

5. Response: { token, user, tenant }

6. Subsequent requests:
   Header: Authorization: Bearer <token>
    Header: X-Tenant: <tenant-ulid>

   Middleware chain:
   a) InitializeTenancyByRequestData — sets tenant context from header
   b) auth:sanctum — resolves token via PersonalAccessToken
      - Since PersonalAccessToken is tenant-scoped (BelongsToTenant),
        the token lookup automatically filters to the current tenant
      - A token minted in Tenant A WILL NOT BE FOUND when Tenant B is active
   c) ValidateTenant — checks user belongs to this tenant via pivot
```

**Key invariant**: A Sanctum token is **tenant-bound**. The same user authenticating in Tenant A gets a different token than in Tenant B. The token's `tenant_id` column ensures the token can only be used when that tenant is the active context.

### 2.2 Cross-tenant token behavior

```
User belongs to Tenant A and Tenant B.

Tenant A context:
  POST /api/v1/auth/login [X-Tenant: A] → token_A
  GET /api/v1/missions [Authorization: Bearer token_A, X-Tenant: A] → 200
  GET /api/v1/missions [Authorization: Bearer token_A, X-Tenant: B] → 401
    (PersonalAccessToken lookup scoped to Tenant B — token_A has tenant_id=A, not found)

Tenant B context:
  POST /api/v1/auth/login [X-Tenant: B] → token_B
  GET /api/v1/missions [Authorization: Bearer token_B, X-Tenant: B] → 200
```

Users need separate tokens per tenant context. This is by design — it mirrors how SaaS platforms work (e.g., GitHub tokens per org).

### 2.3 Sanctum token model — remains tenant-scoped

**File**: `app/Models/PersonalAccessToken.php` — already has `BelongsToTenant`. No change needed.

**Verification**: Confirm the Sanctum custom model is registered:
```php
// AppServiceProvider::boot()
Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
```

### 2.4 Membership actions (single write path)

```php
final class AddTenantMemberAction
{
    public function handle(Tenant $tenant, User $user, string $role = 'member'): void
    {
        $tenant->members()->syncWithoutDetaching([
            $user->getKey() => ['role' => $role],
        ]);
    }
}
```

### 2.5 Token revocation on tenant membership removal

When a user is removed from a tenant, route membership updates through a dedicated action/service and revoke tokens there (do not rely on implicit pivot observers).

```php
final class RemoveTenantMemberAction
{
    public function handle(Tenant $tenant, User $user): void
    {
        $tenant->members()->detach($user->getKey());

        PersonalAccessToken::query()
            ->where('tokenable_id', $user->getKey())
            ->where('tenant_id', $tenant->id)
            ->delete();
    }
}
```

### 2.6 Middleware order for protected API routes

```php
Route::middleware([
    InitializeTenancyByRequestData::class,   // 1: set tenant context
    EnsureTenantIsInitialized::class,         // 2: require X-Tenant + validate identifier format
    'auth:sanctum',                           // 3: resolve token (tenant-scoped)
    ValidateTenant::class,                    // 4: tenant exists/is active + user is member
])->group(function () {
    // All resource routes
});
```

### 2.7 EnsureTenantIsInitialized middleware

**File**: `app/Http/Middleware/EnsureTenantIsInitialized.php`

```php
public function handle(Request $request, Closure $next): Response
{
    if (! tenancy()->initialized) {
        return response()->json([
            'message' => 'X-Tenant header is required.',
            'code' => 'TENANT_REQUIRED',
        ], 422);
    }

    $tenantId = tenant('id');
    if (! Str::isUlid($tenantId)) {
        return response()->json([
            'message' => 'Invalid tenant identifier format.',
            'code' => 'INVALID_TENANT_FORMAT',
        ], 422);
    }

    return $next($request);
}
```

### 2.8 ValidateTenant middleware — pivot-based membership

**File**: `app/Http/Middleware/ValidateTenant.php`

```php
public function handle(Request $request, Closure $next): Response
{
    $tenantId = tenant('id');

    // Tenant must exist and be active
    $tenant = Tenant::find($tenantId);
    if (! $tenant || ! $tenant->is_active) {
        return response()->json([
            'message' => 'Tenant not found or disabled.',
            'code' => 'TENANT_INACTIVE',
        ], 403);
    }

    // Authenticated user must be a member of this tenant
    // (No tenant_id on User — all membership is in the pivot)
    if ($user = $request->user()) {
        if (! $user->belongsToTenant($tenantId)) {
            Log::channel('tenant')->warning('User-tenant membership mismatch', [
                'user_ulid' => $user->ulid,
                'tenant_id' => $tenantId,
            ]);
            return response()->json([
                'message' => 'User is not a member of this fellowship.',
                'code' => 'TENANT_USER_MISMATCH',
            ], 403);
        }
    }

    return $next($request);
}
```

**⚠️ No super-admin bypass in middleware**: Super admin access is handled by policies (Phase 6). The middleware uniformly enforces pivot membership for all users. A super admin who is not in the pivot cannot access the tenant — they must be added first.

### 2.10 Route structure for clean tenancy boundaries

```php
// === PUBLIC — NO tenancy, NO auth ===
Route::withoutMiddleware([InitializeTenancyByRequestData::class])->group(function () {
    // Preserve existing hardening middleware (signatures/throttles) while excluding request-header tenancy.
    // Webhooks must resolve tenant from signed payload metadata before touching tenant-owned tables.
    Route::post('v1/paystack/ipn', [WebhookController::class, 'paystack'])
        ->middleware([VerifyPaystackSignature::class, 'throttle:api-webhook', ResolveTenantFromWebhookPayload::class]);
    Route::post('v1/africas-talking/dlr', ...);
    // Keep this endpoint aligned with existing VerifyRequestSignature exclusions.
    Route::get('v1/server-time', ...);
});

// === AUTH — WITH tenancy (needed for tenant-scoped token creation) ===
Route::middleware([InitializeTenancyByRequestData::class])->prefix('v1/auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('register-student', [AuthController::class, 'registerStudent']);
});

// === SOCIAL AUTH — tenant resolved from callback route/domain/state, not custom headers ===
Route::prefix('v1/auth')->group(function () {
    Route::get('social/{provider}/redirect', [AuthController::class, 'socialRedirect']);
    Route::get('social/{provider}/callback/{tenant}', [AuthController::class, 'socialCallback'])
    ->middleware(InitializeTenancyByPath::class);
});

// === PROTECTED — tenancy + auth + tenant validation ===
Route::middleware([
    InitializeTenancyByRequestData::class,
    EnsureTenantIsInitialized::class,
    'auth:sanctum',
    ValidateTenant::class,
])->group(function () {
    Route::prefix('v1')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::put('auth/update-profile', [AuthController::class, 'updateProfile']);
        // ... all resource routes
    });
});
```

**Required config for callback-by-path**:

```php
// config/tenancy.php
'identification' => [
    'tenant_parameter_name' => 'tenant',
],
```

### 2.10.1 ResolveTenantFromWebhookPayload middleware contract

**File**: `app/Http/Middleware/ResolveTenantFromWebhookPayload.php`

Paystack callbacks do not include tenant headers. Resolve tenant from signed payment metadata/reference, then initialize tenancy before controller logic:

```php
public function handle(Request $request, Closure $next): Response
{
    $reference = $request->input('data.reference');

    if (blank($reference)) {
        abort(422, 'Missing provider reference.');
    }

    $payment = Payment::query()->where('provider_reference', $reference)->firstOrFail();
    $tenant = Tenant::query()->findOrFail($payment->tenant_id);

    // Enforce deterministic lookup + replay safety.
    // provider_reference must be UNIQUE at the DB level.
    // Controller handler must be idempotent (ignore already-processed webhook events).

    tenancy()->initialize($tenant);

    try {
        return $next($request);
    } finally {
        tenancy()->end();
    }
}
```

At payment creation time, persist a tenant-linked reference/metadata so callback resolution is deterministic.

### 2.11 Add telemetry logging for tenant failures

**File**: `app/Http/Middleware/ValidateTenant.php`

```php
Log::channel('tenant')->warning('Tenant validation failed', [
    'header_value' => $request->header('X-Tenant'),
    'user' => $request->user()?->ulid,
    'reason' => $failureReason,
    'path' => $request->path(),
    'ip' => $request->ip(),
]);
```

Add `tenant` channel to `config/logging.php`.

---

## Phase 3: Filament Domain/Subdomain-Based Tenancy

### 3.1 Use DomainOrSubdomain middleware for Filament

**File**: `app/Providers/Filament/AdminPanelProvider.php`

Supports both slug subdomains (`fellowship-a.prf.example.org`) and custom domains (`admin.somefellowship.org`).

```php
->middleware([
    \Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain::class,
    \Stancl\Tenancy\Middleware\PreventAccessFromUnwantedDomains::class,
    \Stancl\Tenancy\Middleware\ScopeSessions::class,
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    AuthenticateSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
    SubstituteBindings::class,
    DisableBladeIconComponents::class,
    DispatchServingFilamentEvent::class,
])
```

**⚠️ Order**: Tenancy middleware must run BEFORE session/auth — sessions are tenant-scoped and user lookup needs tenant context.

### 3.2 Update routes/tenant.php

**File**: `routes/tenant.php`

```php
Route::middleware([
    'web',
    \Stancl\Tenancy\Middleware\InitializeTenancyByDomainOrSubdomain::class,
    \Stancl\Tenancy\Middleware\PreventAccessFromUnwantedDomains::class,
    \Stancl\Tenancy\Middleware\ScopeSessions::class,
])->group(function () {
    // Filament routes registered by AdminPanelProvider.
    // Non-Filament tenant web routes go here.
});
```

### 3.3 Pivot-based canAccessPanel

**File**: `app/Models/User.php`

No `tenant_id` on user. Check pivot membership:

```php
public function canAccessPanel(Panel $panel): bool
{
    if (! $this->hasVerifiedEmail()) {
        return false;
    }

    // Tenant panel access — user must belong to this tenant
    if (tenancy()->initialized) {
        $orgDomain = tenant_setting('organization.org_email_domain', null);

        return $this->belongsToTenant(tenant('id'))
            && (blank($orgDomain) || str_ends_with($this->email, '@'.$orgDomain));
    }

    // Central panel — only users with "super admin" role
    return $this->hasRole('super admin');
}
```

### 3.4 Support custom domain assignment

**File**: `app/Models/Tenant.php`

```php
public function addDomain(string $domain): Domain
{
    return $this->domains()->create(['domain' => $domain]);
}
```

### 3.5 Verify Filament resources use tenant-scoped queries

**Action**: Review all 40 Filament resources:
- Ensure no `withoutGlobalScope` calls strip `BelongsToTenant`
- Ensure no raw `DB::` queries bypass `tenant_id`
- `BelongsToTenant` remains on all domain models (Mission, School, Requisition, etc.)
- User is the only model that does NOT have `BelongsToTenant`

### 3.6 Scope Filament navigation by tenant features

```php
public static function canAccess(): bool
{
    if (! AppSetting::isFeatureEnabled(PRFFeature::MISSIONS)) {
        return false;
    }
    return userCan('viewAny mission');
}
```

---

## Phase 4: AppSettings & Config Tenant Safety

### 4.1 Partition AppSetting cache key by tenant

**File**: `app/Models/AppSetting.php`

```php
public static function getCacheKey(): string
{
    $tenantId = tenancy()->initialized ? tenant('id') : 'central';
    return "app_settings_{$tenantId}";
}

public static function get(string $key, mixed $default = null): mixed
{
    if (! tenancy()->initialized) {
        return $default;
    }

    $tenantId = tenant('id');

    $settings = Cache::remember(self::getCacheKey(), 3600, function () use ($tenantId): array {
        return self::query()
            ->where('tenant_id', $tenantId)
            ->mapWithKeys(fn (self $setting) => [$setting->key => $setting->castValue()])
            ->toArray();
    });

    return $settings[$key] ?? $default;
}

public static function clearCache(): void
{
    Cache::forget(self::getCacheKey());
}

public static function set(
    string $key,
    mixed $value,
    string $group = 'general',
    string $type = 'string'
): self {
    if (! tenancy()->initialized) {
        throw new \RuntimeException('Refusing to write AppSetting outside tenant context.');
    }

    $record = self::updateOrCreate(
        ['tenant_id' => tenant('id'), 'key' => $key],
        [
            'tenant_id' => tenant('id'),
            'group' => $group,
            'key' => $key,
            'value' => is_scalar($value) ? (string) $value : json_encode($value),
            'type' => $type,
        ]
    );

    self::clearCache();

    return $record;
}

/**
 * Single source of truth for feature flags.
 */
public static function isFeatureEnabled(PRFFeature $feature): bool
{
    if (in_array($feature, PRFFeature::core(), true)) {
        return true;
    }

    return (bool) self::get("feature.{$feature->value}", false);
}
```

**Cache invalidation hook**: Register an `AppSettingObserver` that calls `AppSetting::clearCache()` on `created`, `updated`, and `deleted`.

### 4.2 Fix AppSetting early-boot loading

**File**: `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    $this->loadSafeDefaults();

    Event::listen(TenancyInitialized::class, function () {
        $this->loadTenantSettings();
    });
}

private function loadSafeDefaults(): void
{
    config([
        'prf.app.global_group' => 'All',
        'prf.app.excluded_emails' => [],
        // ... all defaults
    ]);
}

private function loadTenantSettings(): void
{
    try {
        config([
            'prf.app.global_group' => AppSetting::get('general.global_group', 'All'),
            'prf.app.excluded_emails' => AppSetting::get('organization.excluded_emails', []),
            // ... all settings
        ]);
    } catch (\Throwable $e) {
        Log::warning('Failed to load tenant settings');
    }
}
```

### 4.3 Make AppSettingSeeder runnable in tenant context

**File**: `database/seeders/AppSettingSeeder.php`

```php
public function run(): void
{
    $settings = [
        // Organization
        ['group' => 'organization', 'key' => 'organization.excluded_emails', 'value' => '[]', 'type' => 'array'],
        ['group' => 'organization', 'key' => 'organization.org_email_domain', 'value' => 'example.org', 'type' => 'string'],
        // ... all existing settings ...

        // Feature flags (single source of truth)
        ['group' => 'features', 'key' => 'feature.missions', 'value' => '1', 'type' => 'boolean'],
        ['group' => 'features', 'key' => 'feature.finance', 'value' => '1', 'type' => 'boolean'],
        ['group' => 'features', 'key' => 'feature.e_learning', 'value' => '0', 'type' => 'boolean'],
        ['group' => 'features', 'key' => 'feature.prayer_requests', 'value' => '1', 'type' => 'boolean'],
        ['group' => 'features', 'key' => 'feature.announcements', 'value' => '0', 'type' => 'boolean'],
        ['group' => 'features', 'key' => 'feature.events', 'value' => '1', 'type' => 'boolean'],
        ['group' => 'features', 'key' => 'feature.groups', 'value' => '0', 'type' => 'boolean'],
        ['group' => 'features', 'key' => 'feature.member_management', 'value' => '1', 'type' => 'boolean'],
        ['group' => 'features', 'key' => 'feature.courses', 'value' => '0', 'type' => 'boolean'],
        ['group' => 'features', 'key' => 'feature.payments', 'value' => '0', 'type' => 'boolean'],
    ];

    foreach ($settings as $setting) {
        AppSetting::updateOrCreate(
            ['tenant_id' => tenant('id'), 'key' => $setting['key']],
            ['tenant_id' => tenant('id'), ...$setting],
        );
    }
}
```

### 4.4 Create tenant-safe setting helper

**File**: `app/Helpers/TenantSettings.php`

```php
if (! function_exists('tenant_setting')) {
    function tenant_setting(string $key, mixed $default = null): mixed
    {
        if (tenancy()->initialized) {
            return AppSetting::get($key, $default);
        }
        return config("prf.app.{$key}", $default);
    }
}
```

---

## Phase 5: Database Hardening & RLS

### 5.1 Verify TableRLSManager configuration

**File**: `config/tenancy.php` — `'rls'` section

```php
'rls' => [
    'manager' => Stancl\Tenancy\RLS\PolicyManagers\TableRLSManager::class,
    'user' => ['username' => env('TENANCY_RLS_USERNAME'), 'password' => env('TENANCY_RLS_PASSWORD')],
    'session_variable_name' => 'my.current_tenant',
],
```

**How it works**: `TableRLSManager` queries `information_schema` for columns named `tenant_id`, checks for FK constraints to `tenants.id`, and generates `CREATE POLICY` statements using `current_setting('my.current_tenant') = tenant_id`. The `PostgresRLSBootstrapper` sets `my.current_tenant` on the Postgres session after tenancy initializes.

**Tables excluded from RLS**:
- `users` — global identity table, no `tenant_id`
- `tenants` — central registry
- `domains` — central, FK to `tenants.id`
- `tenant_user` — central membership pivot
- `jobs`, `cache`, `sessions` — Laravel internals
- `pulse_*`, `telescope_*` — monitoring

**Operational note**: For central-domain maintenance (for example, token cleanup), execute inside an explicit tenant context so RLS session variables are set correctly.

**Connection-pooling safety**: If using PgBouncer, run in **session pooling mode** for app/worker connections that rely on `PostgresRLSBootstrapper`. Transaction pooling can lose session variables (`my.current_tenant`) between statements.

### 5.2 Make tenant_id NOT NULL on tenant-owned tables

**Migration**: `2026_07_09_000700_make_tenant_id_not_null.php`

Run ONLY after data backfill is verified. **Excludes `users`, `personal_access_tokens`, and system tables**.

Reason: non-tenant execution paths (CLI, early bootstrap, central callbacks) may still mint central tokens. Keep `personal_access_tokens.tenant_id` nullable and enforce tenant scoping at middleware + model layer.

```php
$tables = [/* tenant-owned only — same list as FK migration */];

foreach ($tables as $table) {
    if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
        DB::statement("ALTER TABLE \"{$table}\" ALTER COLUMN \"tenant_id\" SET NOT NULL");
    }
}
```

### 5.3 Composite unique constraints (tenant-owned tables only)

```php
// Example for schools — NOT for users (users.email is globally unique)
Schema::table('schools', function (Blueprint $table) {
    $table->dropUnique(['slug']);
    $table->unique(['tenant_id', 'slug']);
});

// Same pattern for other tenant-owned tables with globally-unique columns
```

### 5.4 Regenerate RLS policies after every migration

```bash
php artisan tenants:rls
```

---

## Phase 6: Authentication & Authorization Hardening

### 6.1 Policy helper — tenant match against current tenant context

**File**: `app/Policies/Concerns/EnforcesTenantScope.php`

```php
trait EnforcesTenantScope
{
    /**
     * Super admins bypass all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('super admin')) {
            return true;
        }
        return null;
    }

    /**
     * Verifies the record belongs to the current tenant context.
     * Compares the database column (tenant_id) against the active tenant.
     * Does NOT use $user->tenant_id — users are global.
     */
    protected function isTenantMatch(Model $model): bool
    {
        // Defensive check for policy targets that are not tenant-owned models.
        if (! isset($model->tenant_id)) {
            return false;
        }

        return (string) $model->tenant_id === (string) tenant('id');
    }
}
```

**⚠️ No relation-load guard**: [!] The previous version had `$model->relationLoaded('tenant') || ...` which was unsafe. This version always reads `$model->tenant_id` directly from the database column. No relation needs to be loaded.

**Apply to all 65 policies**:

```php
class MissionPolicy
{
    use EnforcesTenantScope;

    public function view(User $user, Mission $mission): bool
    {
        if (! $this->isTenantMatch($mission)) {
            return false;
        }
        return $user->can(Mission::permission('view'));
    }

    public function update(User $user, Mission $mission): bool
    {
        if (! $this->isTenantMatch($mission)) {
            return false;
        }
        return $user->can(Mission::permission('edit'));
    }
}
```

### 6.2 Spatie permission team_id stays synchronized

**File**: `config/permission.php`

```php
'teams' => true,
'team_foreign_key' => 'tenant_id',
```

Without this, `setPermissionsTeamId()` does nothing.

Team context is runtime-only after removing `users.tenant_id`. This is expected: role assignment/read operations must run with `setPermissionsTeamId($tenantId)` already set.

**File**: `app/Providers/TenancyServiceProvider.php`

```php
Event::listen(Events\TenancyInitialized::class, function (Events\TenancyInitialized $event) {
    if ($tenant = $event->tenancy->tenant) {
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
    } else {
        Log::warning('TenancyInitialized event fired with no tenant');
    }
});
```

All role/permission seeders used in provisioning must be idempotent (`firstOrCreate`/`updateOrCreate`, never blind `create`) to tolerate queued retries.

### 6.3 Sanctum middleware order enforced

Protected route order ensures tenancy is initialized before Sanctum token resolution:

```php
[
    InitializeTenancyByRequestData::class,
    EnsureTenantIsInitialized::class,
    'auth:sanctum',
    ValidateTenant::class,
]
```

---

## Phase 7: Feature Flags via AppSetting

### 7.1 PRFFeature enum

**File**: Create `app/Enums/PRFFeature.php`

```php
enum PRFFeature: string
{
    case MISSIONS = 'missions';
    case FINANCE = 'finance';
    case E_LEARNING = 'e_learning';
    case PRAYER_REQUESTS = 'prayer_requests';
    case ANNOUNCEMENTS = 'announcements';
    case EVENTS = 'events';
    case GROUPS = 'groups';
    case MEMBER_MANAGEMENT = 'member_management';
    case COURSES = 'courses';
    case PAYMENTS = 'payments';

    public static function core(): array
    {
        return [self::MISSIONS, self::MEMBER_MANAGEMENT];
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MISSIONS => 'Missions',
            self::FINANCE => 'Finance',
            // ...
        };
    }
}
```

### 7.2 CheckFeature middleware

**File**: `app/Http/Middleware/CheckFeature.php`

```php
public function handle(Request $request, Closure $next, string $feature): Response
{
    try {
        $featureEnum = PRFFeature::from($feature);
    } catch (\ValueError) {
        return response()->json([
            'message' => 'Unknown feature flag.',
            'code' => 'UNKNOWN_FEATURE',
        ], 422);
    }

    if (! AppSetting::isFeatureEnabled($featureEnum)) {
        return response()->json([
            'message' => 'This feature is not enabled for your fellowship.',
            'code' => 'FEATURE_DISABLED',
        ], 403);
    }
    return $next($request);
}
```

### 7.3 Feature gates applied at all layers

- **API routes**: `Route::middleware(['feature:missions'])`
- **Filament resources**: `canAccess()` checks `AppSetting::isFeatureEnabled()`
- **Jobs**: `handle()` checks before executing

---

## Phase 8: Tenant Provisioning & Onboarding

### 8.1 Single authoritative command: `tenants:create`

**File**: `app/Console/Commands/Tenant/CreateTenant.php`

```
tenants:create
  {name : Fellowship name}
  {slug? : Subdomain slug (auto-generated if omitted)}
  {--domain= : Custom domain (e.g., admin.fellowship.org)}
  {--admin-email= : Admin user email (must already exist or be created first)}
  {--confirm-promote-existing-admin : Required to promote an existing global user to tenant super admin}
```

**This command is the ONLY provisioning entry path**. `TenantObserver::created()` only auto-creates the slug subdomain. All provisioning work is executed by `ProvisionTenantJob` (sync from command, async optional for retries).

**Workflow**:
1. Create Tenant (triggers observer → slug subdomain created)
2. If `--domain` provided, create additional Domain record
3. Dispatch provisioning executor:
    - `ProvisionTenantJob::dispatchSync($tenant, $this->option('admin-email'), (bool) $this->option('confirm-promote-existing-admin'))`
    - (Optional retry path uses queued dispatch for failed provisions)
4. Output tenant details

**Admin promotion rule**:
- If `--admin-email` does not exist globally, provisioning creates the user and grants tenant admin role.
- If `--admin-email` already exists globally, provisioning MUST fail unless `--confirm-promote-existing-admin` is passed.
- This prevents accidental privilege escalation of an unrelated existing account.

### 8.2 ProvisionTenantJob

**File**: `app/Jobs/Tenant/ProvisionTenantJob.php`

This job is the provisioning executor and is called by `tenants:create`. Do not dispatch it from arbitrary code paths.

```php
class ProvisionTenantJob implements ShouldQueue
{
    public function __construct(
        public Tenant $tenant,
        public ?string $adminEmail = null,
        public bool $confirmPromoteExistingAdmin = false,
    ) {}

    public function handle(): void
    {
        tenancy()->initialize($this->tenant);

        try {
            // Direct seeder calls — no Artisan::call
            (new \Database\Seeders\RolesAndPermissionsSeeder)->run();
            (new \Database\Seeders\AppSettingSeeder)->run();
            (new \Database\Seeders\GroupSeeder)->run();

            if ($this->adminEmail) {
                $user = User::query()->where('email', $this->adminEmail)->first();

                if ($user === null) {
                    $user = User::create([
                        'email' => $this->adminEmail,
                        'name' => $this->tenant->name . ' Admin',
                        'password' => bcrypt(Str::random(32)),
                    ]);
                } elseif (! $this->confirmPromoteExistingAdmin) {
                    throw new \RuntimeException('Refusing to promote existing global user without --confirm-promote-existing-admin.');
                }

                $user->assignRole('super admin');

                app(\App\Actions\Tenant\AddTenantMemberAction::class)
                    ->handle($this->tenant, $user, 'admin');

                $user->notify(new Tenant\WelcomeNotification($this->tenant));
            }
        } catch (\Throwable $e) {
            Log::error('Tenant provisioning failed', ['tenant' => $this->tenant->id]);
            throw $e;
        } finally {
            tenancy()->end();
        }
    }
}
```

### 8.3 Create tenants:disable command

**File**: `app/Console/Commands/Tenant/DisableTenant.php`

```php
public function handle(): void
{
    $tenant = Tenant::where('slug', $this->argument('tenant'))->firstOrFail();
    $tenant->update(['is_active' => false]);

    // Revoke tokens in explicit tenant context so RLS/session scoping remains valid.
    tenancy()->initialize($tenant);

    try {
        PersonalAccessToken::query()
            ->where('tenant_id', $tenant->id)
            ->delete();
    } finally {
        tenancy()->end();
    }
}
```

For bulk operations, iterate tenants and run the same initialize -> revoke -> end flow per tenant.

### 8.4 Create tenants:add-member command

**File**: `app/Console/Commands/Tenant/AddMemberCommand.php`

```
tenants:add-member {tenant} {email} {--role=member}
```

---

## Phase 9: Existing Data Migration

### 9.1 Verify existing backfill migration

**File**: `database/migrations/2026_06_30_201722_add_tenant_id_to_domain_tables.php`

Backfills `tenant_id` on 84 tables. Verify in staging: zero nulls, FK integrity.

### 9.2 Backfill tenant_user pivot

Run pivot backfill migration (Phase 1.6) before dropping `users.tenant_id`.

### 9.3 Drop users.tenant_id

Run the migration from Phase 1.1.

### 9.4 Restore global email unique constraint

Precondition: resolve duplicate `users.email` values before adding the index.

```sql
SELECT email, COUNT(*)
FROM users
GROUP BY email
HAVING COUNT(*) > 1;
```

Merge/repair duplicates first, then add the unique index.

Ensure a global unique email constraint exists on `users` (idempotent migration).

```php
public function up(): void
{
    DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS users_email_unique ON users (email)');
}

public function down(): void
{
    DB::statement('DROP INDEX IF EXISTS users_email_unique');
}
```

### 9.5 Enforce NOT NULL + FK constraints (tenant-owned tables only)

Explicitly exclude `users`, `personal_access_tokens`, and system tables from NOT NULL enforcement. Keep FK constraints for `personal_access_tokens`.

### 9.6 Regenerate RLS policies

```bash
php artisan tenants:rls
```

### 9.7 Validate data integrity command

**File**: `app/Console/Commands/Tenant/ValidateDataIntegrity.php`

```php
public function handle(): int
{
    $errors = [];

    // 1) No orphan tenant_id values on tenant-owned tables
    // 2) No null tenant_id on required tenant-owned tables
    // 3) tenant_user rows point to existing users and tenants
    // 4) Duplicate global user emails are zero

    $orphanRows = DB::table('missions as m')
        ->leftJoin('tenants as t', 't.id', '=', 'm.tenant_id')
        ->whereNull('t.id')
        ->count();

    if ($orphanRows > 0) {
        $errors[] = "Found {$orphanRows} mission rows with orphan tenant_id values.";
    }

    $nullTenantRows = DB::table('missions')->whereNull('tenant_id')->count();
    if ($nullTenantRows > 0) {
        $errors[] = "Found {$nullTenantRows} mission rows with null tenant_id.";
    }

    $invalidPivotRows = DB::table('tenant_user as tu')
        ->leftJoin('tenants as t', 't.id', '=', 'tu.tenant_id')
        ->leftJoin('users as u', 'u.id', '=', 'tu.user_id')
        ->where(function ($q) {
            $q->whereNull('t.id')->orWhereNull('u.id');
        })
        ->count();

    if ($invalidPivotRows > 0) {
        $errors[] = "Found {$invalidPivotRows} tenant_user rows with missing tenant/user references.";
    }

    $duplicateEmails = DB::table('users')
        ->select('email')
        ->groupBy('email')
        ->havingRaw('COUNT(*) > 1')
        ->count();

    if ($duplicateEmails > 0) {
        $errors[] = "Found {$duplicateEmails} duplicated global user email values.";
    }

    if (! empty($errors)) {
        foreach ($errors as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }

    $this->info('Tenant data integrity checks passed.');

    return self::SUCCESS;
}
```

---

## Implementation Guardrails

These rules are mandatory during rollout and after go-live.

1. Tenant membership writes MUST go through dedicated actions/services.
    - Use `AddTenantMemberAction` and `RemoveTenantMemberAction` (or equivalent domain service).
    - Do not call `attach`, `detach`, `sync`, or `syncWithoutDetaching` directly from controllers, jobs, seeders, or ad-hoc scripts.

2. Token issuance MUST always be tenant-contextual.
    - Every created token must persist `tenant_id`.
    - Token revocation on membership removal must happen in the same action/service transaction path.

3. Protected tenant routes MUST preserve middleware order.
    - `InitializeTenancyByRequestData` -> `EnsureTenantIsInitialized` -> `auth:sanctum` -> `ValidateTenant`.
    - No bypasses in middleware for super admin.

4. Super admin elevation is policy-only, never middleware-only.
    - Policies may allow super admin cross-tenant management actions.
    - Runtime tenant access still requires explicit membership in `tenant_user`.

5. AppSetting is the only source of truth for tenant feature toggles.
    - Do not split feature flags across config files, env variables, and tenant settings.
    - Each toggle must have a documented key, default, and owner.

6. Migration order is fixed and cannot be reordered.
    - Use an **expand -> migrate -> contract** sequence for zero downtime.
    - Expand: additive schema only (new tables/columns/indexes), deploy compatible code.
    - Migrate: backfill data and dual-read/dual-write compatibility paths.
    - Contract: remove legacy columns/constraints only after compatibility validation.
    - Regenerate RLS policies after schema changes.

7. Destructive DDL requires preflight checks.
    - Before dropping columns or setting NOT NULL, verify zero-null and FK integrity in staging.
    - Use idempotent DDL (`IF EXISTS` / `IF NOT EXISTS`) where available.

8. Central-domain maintenance touching tenant-owned tables MUST run in explicit tenant context.
    - Batch jobs and cleanup commands must iterate tenants and initialize tenancy per tenant.
    - Do not query tenant-owned tables from central context without tenancy initialized.

9. Any new tenant-owned table must be RLS-ready at creation time.
    - Include `tenant_id` + FK to `tenants.id` in the same migration.
    - Add table to tenant-owned migration inventories and verification checklists.

10. CI gates must enforce these guardrails.
     - Add tests for cross-tenant rejection and in-tenant success on every new tenant-owned endpoint.
     - Add a static check/lint rule to block direct `attach/detach/sync` usage outside approved membership actions.

### Guardrail Compliance Checklist

| Guardrail | Owner | Automated Check | Manual Check | Status |
|-----------|-------|-----------------|--------------|--------|
| Membership writes via actions only | API lead | Static scan blocks direct pivot mutation outside approved actions | PR review on membership-related diffs | Not started |
| Tenant-scoped token lifecycle | Auth lead | Feature tests for token create/revoke per tenant | Spot-check token rows after member removal | Not started |
| Middleware order preserved | Platform lead | Route tests assert 401/403 behavior for wrong tenant | Middleware stack review in route groups | Not started |
| Policy vs middleware boundary | Security lead | Authorization tests for super admin and non-member paths | Policy review against access matrix | Not started |
| AppSetting as single toggle source | Product engineering lead | Config test ensures feature reads only from AppSetting service | Toggle inventory review | Not started |
| Migration order + idempotent DDL | Data lead | Migration smoke run on fresh and existing DB snapshots | Staging runbook sign-off | Not started |
| RLS context for central maintenance | Platform lead | Command tests iterate tenants with tenancy initialized | Dry run of maintenance commands in staging | Not started |
| New tenant-owned tables are RLS-ready | DB lead | Schema test checks tenant_id + FK on new tenant-owned tables | Migration checklist review | Not started |
| CI guardrails active | DevEx lead | CI workflow fails on guardrail violations | Quarterly pipeline audit | Not started |

---

## Phase 10: Testing & Verification

### 10.1 Update test infrastructure

**File**: `tests/Pest.php`

```php
function createTenant(): Tenant
{
    // TenantObserver::created() auto-creates the slug domain.
    return Tenant::factory()->create();
}

function initTenancy(Tenant $tenant): void
{
    tenancy()->initialize($tenant);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
}

// Seed roles once in a global test bootstrap/beforeAll, not inside helpers.

function actingAsTenantUser(Tenant $tenant, array $roles = ['super admin']): User
{
    initTenancy($tenant);
    $user = User::factory()->create(); // No tenant_id
    $user->assignRole($roles);
    app(\App\Actions\Tenant\AddTenantMemberAction::class)->handle($tenant, $user, 'admin');

    test()->actingAs($user);

    return $user;
}

function tenantHeaders(Tenant $tenant): array
{
    return ['X-Tenant' => $tenant->id];
}
```

### 10.2 Authentication & token tests

**File**: `tests/Feature/Tenancy/AuthenticationTest.php`

```php
it('issues tenant-bound tokens on login', function () {
    $tenant = createTenant();
    initTenancy($tenant);
    $user = User::factory()->create();
    app(\App\Actions\Tenant\AddTenantMemberAction::class)->handle($tenant, $user, 'member');

    $response = $this->withHeader('X-Tenant', $tenant->id)
        ->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $response->assertOk();
    $response->assertJsonStructure(['token']);

    // Token belongs to this tenant
    $token = PersonalAccessToken::where('tokenable_id', $user->getKey())->first();
    expect($token->tenant_id)->toBe($tenant->id);
});

it('rejects token used in wrong tenant', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();
    initTenancy($tenantA);
    $user = User::factory()->create();
    app(\App\Actions\Tenant\AddTenantMemberAction::class)->handle($tenantA, $user, 'member');
    app(\App\Actions\Tenant\AddTenantMemberAction::class)->handle($tenantB, $user, 'member');

    // Login in Tenant A → token A
    $loginResponse = $this->withHeader('X-Tenant', $tenantA->id)
        ->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password']);
    $tokenA = $loginResponse->json('token');

    // Use token A in Tenant B → rejected
    $response = $this->withHeader('X-Tenant', $tenantB->id)
        ->withHeader('Authorization', "Bearer {$tokenA}")
        ->getJson('/api/v1/auth/me');

    $response->assertStatus(401);
});

it('requires X-Tenant header', function () {
    $response = $this->getJson('/api/v1/missions');
    $response->assertStatus(422);
    $response->assertJson(['code' => 'TENANT_REQUIRED']);
});

it('rejects non-member user', function () {
    $tenant = createTenant();
    initTenancy($tenant);
    $user = User::factory()->create(); // Not attached to tenant

    $this->actingAs($user)->withHeader('X-Tenant', $tenant->id)
        ->getJson('/api/v1/missions')
        ->assertStatus(403);
});
```

### 10.3 API isolation tests

**File**: `tests/Feature/Tenancy/APIIsolationTest.php`

```php
it('prevents cross-tenant read', function () {
    $tenantA = createTenant();
    initTenancy($tenantA);
    $userA = actingAsTenantUser($tenantA);
    $mission = Mission::factory()->create();

    $tenantB = createTenant();
    initTenancy($tenantB);
    $userB = actingAsTenantUser($tenantB);

    $this->actingAs($userB)
        ->withHeaders(tenantHeaders($tenantB))
        ->getJson("/api/v1/missions/{$mission->ulid}")
        ->assertStatus(403);
});

it('lists only own tenant records', function () {
    $tenantA = createTenant();
    initTenancy($tenantA);
    $userA = actingAsTenantUser($tenantA);
    Mission::factory()->count(3)->create();

    $tenantB = createTenant();
    initTenancy($tenantB);
    $userB = actingAsTenantUser($tenantB);
    Mission::factory()->count(5)->create();

    $response = $this->actingAs($userB)
        ->withHeaders(tenantHeaders($tenantB))
        ->getJson('/api/v1/missions');
    $response->assertJsonCount(5, 'data');
});
```

### 10.4 Filament isolation tests

**File**: `tests/Feature/Tenancy/FilamentIsolationTest.php`

```php
it('prevents cross-tenant panel access', function () {
    $tenantA = createTenant();
    $tenantB = createTenant();
    initTenancy($tenantA);
    $userA = actingAsTenantUser($tenantA);

    initTenancy($tenantB);

    $this->actingAs($userA)->get('/admin')->assertStatus(403);
});
```

### 10.5 Feature flag tests

```php
it('blocks disabled features via API', function () {
    $tenant = createTenant();
    initTenancy($tenant);
    $user = actingAsTenantUser($tenant);
    AppSetting::set('feature.missions', '0', 'features', 'boolean');

    $this->actingAs($user)
        ->withHeaders(tenantHeaders($tenant))
        ->getJson('/api/v1/missions')
        ->assertStatus(403);
});
```

### 10.6 RLS tests (Postgres only)

```php
it('RLS isolates tenant data at DB level', function () {
    $tenantA = createTenant();
    initTenancy($tenantA);
    Mission::factory()->count(3)->create();

    $tenantB = createTenant();
    initTenancy($tenantB);

    expect(DB::table('missions')->count())->toBe(0);
});
```

### 10.7 AppSetting isolation tests

```php
it('isolates AppSetting by tenant', function () {
    $a = createTenant();
    initTenancy($a);
    AppSetting::set('test.k', 'v-a', 'tests', 'string');

    $b = createTenant();
    initTenancy($b);
    AppSetting::set('test.k', 'v-b', 'tests', 'string');

    initTenancy($a);
    expect(AppSetting::get('test.k'))->toBe('v-a');
});
```

### 10.8 Membership tests

```php
it('allows multi-tenant user with separate tokens', function () {
    $user = User::factory()->create();
    $tenantA = createTenant();
    $tenantB = createTenant();
    app(\App\Actions\Tenant\AddTenantMemberAction::class)->handle($tenantA, $user, 'member');
    app(\App\Actions\Tenant\AddTenantMemberAction::class)->handle($tenantB, $user, 'member');

    initTenancy($tenantA);
    $tokenA = $user->createToken('a')->plainTextToken;

    initTenancy($tenantB);
    $tokenB = $user->createToken('b')->plainTextToken;

    // tokenA works in Tenant A
    $this->withHeader('X-Tenant', $tenantA->id)
        ->withHeader('Authorization', "Bearer {$tokenA}")
        ->getJson('/api/v1/auth/me')
        ->assertOk();

    // tokenA fails in Tenant B
    $this->withHeader('X-Tenant', $tenantB->id)
        ->withHeader('Authorization', "Bearer {$tokenA}")
        ->getJson('/api/v1/auth/me')
        ->assertStatus(401);

    // tokenB works in Tenant B
    $this->withHeader('X-Tenant', $tenantB->id)
        ->withHeader('Authorization', "Bearer {$tokenB}")
        ->getJson('/api/v1/auth/me')
        ->assertOk();
});
```

---

## Deployment Order

```
1. Environment: TENANCY_* vars
2. Expand phase (safe additive schema):
    - 2026_07_09_000100_create_tenant_user_table
    - 2026_07_09_000200_add_slug_config_to_tenants_table
    - 2026_07_09_000300_add_tenant_id_foreign_keys
    - add_tenant_aware_uniques
3. Deploy compatibility code (supports both legacy and new paths)
4. Migrate phase:
    - 2026_07_09_000400_backfill_tenant_user
    - 2026_07_09_000600_restore_email_unique
5. RLS: php artisan tenants:rls
6. Validate: php artisan tenants:validate-data
7. Test: php artisan test --filter=Tenancy
8. Contract phase (destructive after validation):
    - 2026_07_09_000500_drop_tenant_id_from_users
    - 2026_07_09_000700_make_tenant_id_not_null (exclude users, personal_access_tokens)
9. RLS: php artisan tenants:rls
10. Final validation + smoke tests
11. Go live
```

---

## Files Summary

### New files (31)
| File | Phase |
|------|-------|
| `app/Http/Middleware/EnsureTenantIsInitialized.php` | 2.6 |
| `app/Http/Middleware/ValidateTenant.php` | 2.7 |
| `app/Http/Middleware/ResolveTenantFromWebhookPayload.php` | 2.10.1 |
| `app/Http/Middleware/CheckFeature.php` | 7.2 |
| `app/Enums/PRFFeature.php` | 7.1 |
| `app/Observers/TenantObserver.php` | 1.7 |
| `app/Observers/AppSettingObserver.php` | 4.1 |
| `app/Policies/Concerns/EnforcesTenantScope.php` | 6.1 |
| `app/Jobs/Tenant/ProvisionTenantJob.php` | 8.2 |
| `app/Console/Commands/Tenant/CreateTenant.php` | 8.1 |
| `app/Console/Commands/Tenant/DisableTenant.php` | 8.3 |
| `app/Console/Commands/Tenant/AddMemberCommand.php` | 8.4 |
| `app/Console/Commands/Tenant/ValidateDataIntegrity.php` | 9.x |
| `app/Notifications/Tenant/WelcomeNotification.php` | 8.x |
| `app/Helpers/TenantSettings.php` | 4.4 |
| `app/Actions/Tenant/AddTenantMemberAction.php` | 2.4 |
| `app/Actions/Tenant/RemoveTenantMemberAction.php` | 2.4 |
| `database/migrations/2026_07_09_000500_drop_tenant_id_from_users.php` | 1.1 |
| `database/migrations/2026_07_09_000100_create_tenant_user_table.php` | 1.3 |
| `database/migrations/2026_07_09_000200_add_slug_config_to_tenants_table.php` | 1.x |
| `database/migrations/2026_07_09_000300_add_tenant_id_foreign_keys.php` | 0.5 |
| `database/migrations/2026_07_09_000400_backfill_tenant_user.php` | 1.6 |
| `database/migrations/2026_07_09_000600_restore_email_unique.php` | 9.4 |
| `database/migrations/2026_07_09_000700_make_tenant_id_not_null.php` | 5.2 |
| `database/seeders/TenantSeeder.php` | 1.9 |
| `tests/Feature/Tenancy/AuthenticationTest.php` | 10.2 |
| `tests/Feature/Tenancy/APIIsolationTest.php` | 10.3 |
| `tests/Feature/Tenancy/FilamentIsolationTest.php` | 10.4 |
| `tests/Feature/Tenancy/FeatureFlagTest.php` | 10.5 |
| `tests/Feature/Tenancy/RLSTest.php` | 10.6 |
| `tests/Feature/Tenancy/AppSettingIsolationTest.php` | 10.7 |
| `tests/Feature/Tenancy/MembershipTest.php` | 10.8 |

### Modified files (13)
| File | Change |
|------|--------|
| `config/tenancy.php` | bootstrappers, central_domains, prefix/suffix |
| `config/permission.php` | Enable Spatie teams + tenant team key |
| `bootstrap/app.php` | Middleware registration |
| `routes/api/v1.php` | Route group restructuring |
| `app/Providers/AppServiceProvider.php` | Deferred AppSetting loading |
| `app/Providers/TenancyServiceProvider.php` | Team ID logging |
| `app/Providers/Filament/AdminPanelProvider.php` | Tenancy middleware |
| `app/Models/User.php` | Remove BelongsToTenant + tenant_id, add tenants() pivot, fix canAccessPanel |
| `app/Models/Tenant.php` | Add members() pivot, addDomain() |
| `app/Models/PersonalAccessToken.php` | Verify BelongsToTenant is present (was already) |
| `app/Models/AppSetting.php` | Tenant-partitioned cache, isFeatureEnabled() |
| `database/seeders/AppSettingSeeder.php` | Add feature flag settings |
| `database/factories/TenantFactory.php` | Add slug, is_active |
| `tests/Pest.php` | Test helpers |
