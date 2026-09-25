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
