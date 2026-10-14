# Plan: a uniform codebase: tenant-owned integrations, event-driven side effects, standard jobs, tests and agent guidance

## Context
Ten read-only audits covered the whole repo. They found five kinds of problem:
1. **Agent guidance is stale and duplicated.**
   - The guidance says L12, Pest 4 and Pint. The repo actually runs L13, Pest 5 and Mago.
   - The guide is pasted into 5 files, so agents write code that doesn't fit.
2. **Security and correctness bugs:**
   - Media can be deleted with no scope check.
   - The v2 routes skip tenancy.
   - The Paystack webhook is broken.
   - Tenant secrets silently fall back to `env`, and config leaks between tenants.
   - Filament checks permissions that don't exist.
   - Scheduled commands run outside any tenant.
   - There are Carbon mistakes, and pledge amounts are 100× too small.
   - Members of tenants without an org domain get made-up `@gmail.com` addresses that belong to strangers.
3. **Side effects are scattered:**
   - They are spread across observers, controllers, jobs, one listener and Filament.
   - 22 query-level updates skip them silently.
   - Bulk approvals in Filament skip the ledger writes.
   - `ShouldBeUnique` without a `uniqueId` drops announcements.
   - Nothing runs after commit.
   - The queue's `retry_after` (90s) is lower than the job timeouts (up to 600s).
4. **Jobs and notifications don't share a shape:**
   - Jobs mix 159 shapes (constructor order, return types, traits).
   - 25 notifications are named inconsistently.
   - Integrations are tied to one vendor (Gemini, SMS), and 3 jobs call Gemini directly.
5. **Payment polling won't scale:**
   - It runs every 3 minutes with sync, sequential Paystack calls using the env keys.
   - It cancels checkouts that are still in progress and skips rows during pagination.
   - It can downgrade payments that already succeeded.

**Decisions made with you:**
- Agent guidance lives in `.ai/guidelines`, managed by Boost.
- UpdateJob loads the model, calls `update()` and returns it.
- The Paystack webhook carries the tenant in its URL.
- Paystack, SMS, FCM and AI are per-tenant and fail closed when not configured.
- Pledges are stored in integer KES.
- Adopt `laravel/ai`. This is a new dependency, which you approved.
- Side effects move to domain events and listeners.
- Notifications are named `{Model}/{Model}{Event}Notification`.
- Laravel 13 model attributes (`#[Fillable]` etc.).
- Tests follow a standard Pest template under `tests/Feature/Api`.
- Member identity has two modes per tenant (org domain or personal email). Tenant panel access is permission-based. Existing data is fixed with a command that has a dry-run mode.

**Scope limits:** you handle commits. Tests are written in this task because you asked for them.

Each phase ends green (tests, Mago, PHPStan level 10), so each one can be reviewed and committed on its own.

---

## Phase 1: Agent guidance (target conventions, written first)
- Create these files in `.ai/guidelines/`. Boost v2 merges them into every agent file.
  - `prf-architecture.md`
  - `prf-jobs-and-side-effects.md`
  - `prf-notifications.md`
  - `prf-tenancy-and-integrations.md`
  - `prf-testing.md`
  - `mago.md`
- Delete the pasted copies of the guide from CLAUDE.md, AGENTS.md (×2), GEMINI.md (×2) and `.github/copilot-instructions.md`.
- Replace `.clinerules` with a pointer to AGENTS.md.
- Run `php artisan boost:update --no-interaction`.
- **Architecture guide:**
  - Base `Controller`: index, show and destroy, plus `?sort=`, `?include=`, `?filter[]=` and `?limit=`.
  - Checklist for a new CRUD resource: migration (with `tenant_id` FK, `ulid()->unique()`, `softDeletes`), model, factory, permissions in `config/prf/roles.php`, one-line policy, requests, jobs, resource, controller, routes, Feature test, and a `PRFMorphType` case where needed.
  - Model template: attributes, `HasQueryBuilderCapabilities`, the standard trait set, typed relations, `Attribute::make`, `#[Scope]`.
  - FormRequest `authorize()` uses `Model::permission()`, with array rules.
  - Resources: kebab-case `entity`, `->value` for enums.
  - Money is integer KES. Carbon: use `copy()` and absolute `diffInDays`. Use `HandlesMedia` for media.
- The other guides contain the rules in Phases 5–8 below. Each one includes a copy-ready template.
- `prf-testing.md` states that tests run in Docker (`make test`, `make test-filter`, `make test-file`) and never directly on the host.
- `prf-jobs-and-side-effects.md` states the rule against query-level writes and the `updateQuietly` exception.

---

## Phase 2: Critical bugs and security (each fix has a test)
1. **Media.** Create `app/Http/Controllers/Concerns/HandlesMedia` to replace the 12 copied `attachMedia`/`getMedia`/`deleteMedia` actions. It resolves the owner by ulid, calls `authorize('update'|'view')`, and deletes only through `$owner->media()`. Delete returns a real 204.
2. **v2 routes.** Wrap `routes/api/v2.php` in the `tenant.initialized, auth:sanctum, tenant.validate` middleware.
3. **Wrong config key.** Replace `prf.giving.*` with `prf.app.giving.*` in `PledgeForm.php:160`, `ResolvePledgeTenant.php:26` and `DispatchDueRemindersCommand.php:34-35`.
4. **Carbon.**
   - Use `copy()` in `DispatchDueRemindersCommand.php:37`.
   - The weather window check has its sign reversed. Fix it in `MissionObserver.php:46` and in both `GenerateMissing…` commands (these move to listeners in Phase 6).
5. **Missing policies.** Add `Pledge`, `PledgeInstallment`, `AppSetting`, `Student`, `TransferRate`, `User` and `AnnouncementGroup` policies. Replace the `Gate::policy` call with `#[UsePolicy]` on PRFEvent.
6. **Filament permissions.** About 189 `userCan('…')` calls hand-type the permission string. Change them to `userCan(Model::permission('…'))` and add the approve-any, assign-approver, export and request-review permissions to `config/prf/roles.php`. Add a test that every permission Filament references actually exists.
7. **Pledge amounts.** In `ReconcilePaymentJob.php:39`, remove the `/100`.
8. **Chunk skip bug.** `ReconcilePaymentsCommand` loses rows because it updates the column it filters on inside `chunk()`. Switch to `chunkById`. Its `$matched` counter is also always 0, so fix that.
9. **Pledge reminders.** `PledgeDueNotification` is sent to a raw email string, so it never arrives. Send it with `Notification::route('mail', $pledge->email)`.
10. **Event update.** `API/EventController.php:44` reads `$event->id`, but UpdateJob returns void. Phase 5 fixes this.
11. **Legacy ULIDs.** Use `Utils::generateULID()` in `ImportLegacySQLCommand.php:988`.
12. **Listener registered twice.** Remove the manual `Event::listen` for the MissionSubscription listener. First check `php artisan event:list` to confirm it is registered twice.
13. **Dead code.** Delete:
    - `EnforcesTenantScope`
    - `AfricasTalkingController`
    - `SMS/CheckIfSenderIsBlacklistedJob`
    - `AccountingEvent/CreateDefaultRequisitionJob`
    - `Notifications/Mission/FinancialsNotification`
    - `ResolveTenantFromWebhookPayload` (it queries a column that doesn't exist)
    - the empty `AccountingEventObserver`

---

## Phase 3: Member email modes and tenant creation (security-critical)
**The problem.** When a tenant has no org domain, `Utils::getOrgEmailDomain()` falls back to `gmail.com` (`Utils.php:43`). `MemberObserver` then creates identities like `john.doe@gmail.com`, which are real accounts owned by strangers. Those strangers can sign in through Google (`LoginSocialUserJob.php:49`, `OAuthController` auto-link) and pass the panel gate (`User::canAccessPanel`, `User.php:113-115`). Members' emails go to them too (`Member` has no `routeNotificationForMail`). Separately, `ProvisionTenantJob.php:39` saves the app **hosting** domain as the email domain.

**Design: two explicit modes per tenant**
- New enum `App\Enums\PRFMemberEmailMode` (int), stored in the tenant setting `organization.member_email_mode`:
  - `ORGANISATION_DOMAIN`: the tenant owns a Google Workspace domain, stored in `organization.org_email_domain`. Members get `first.last@domain`, and the Workspace export and credential emails apply.
  - `PERSONAL`: a member's identity is their `personal_email`, for example their own Gmail. Nothing is generated.
- **`App\Services\Members\MemberIdentityService`** is the single place that decides a member's login email:
  - `loginEmailFor(Member)`
  - `generateOrgEmail()`, which checks for collisions against **Users**, including soft-deleted ones, as well as Members
  - `provisionUser(Member): User`
- **`Utils::getOrgEmailDomain()`** returns `?string`. There's no gmail fallback, and it refuses public webmail domains (gmail.com, yahoo.com, outlook.com, …), using a list in config. `generatePRFEmail` moves into the service.
- **Member creation.** The identity logic moves out of the observer into `Member/CreateJob` (this ties in with Phase 6).
  - **Org mode:** generate an address and create the User.
  - **Personal mode:** `email = personal_email`, then `User::firstOrCreate` by that email with no overwrite. If a User already exists (the same person in another tenant), attach them with `AddTenantMemberAction`.
  - Imports (`UploadImport`, `WebUploadImport`) call the same job.
- **Member deletion and updates.** For a shared User, deleting a member detaches them from the tenant (`RemoveTenantMemberAction`). The User is deleted only when it belongs to no other tenant. Renames update `users.name` only when the User belongs to one tenant.
- **Changing `personal_email`** in personal mode also updates `users.email`, in a transaction and after a uniqueness check.
- **Mail routing.** `Member::routeNotificationForMail()` returns the `email` column in org mode and `personal_email` in personal mode.
- **Panel access.** Add an `access tenant panel` permission in `config/prf/roles.php`, granted to super admin and the leadership/desk roles (not `member` or `student`). `canAccessPanel` checks tenant membership and that permission, and no longer looks at the email domain.
- **Social login.** `LoginSocialUserJob` and `LoginSocialLeaderJob` add an explicit `belongsToTenant(tenant('id'))` check. `OAuthController` stops creating orphan Users. Set `auth-existing-unlinked-users` to false in `config/socialstream.php`.
- **Registration.** When the email already has a User, `RegisterJob`/`RegisterRequest` attach that User to the tenant instead of failing on `unique:users,email`.
- **Students.** `Student::getEmailAttribute` and `RegisterStudentJob` follow the same mode.
- **Desk emails and exclusions.** Match on `email` or `personal_email` according to the mode, through one helper. Fix the `UserSeeder:367` bug, where the approvals user gets the `student` role.

**Automatic Google Workspace accounts (org-domain mode)**
Today someone has to create each Workspace mailbox by hand after the email is generated, and collisions are hard to fix afterwards. Workspace becomes the source of truth for whether an address is taken, and accounts are created automatically.
- **Integration:** `PRFIntegration::GOOGLE_WORKSPACE`, required only in org mode, per tenant, fail closed. Settings:
  - `google_workspace.service_account_json` (secret, domain-wide delegation)
  - `google_workspace.admin_subject` (the Workspace admin to impersonate)
  - `google_workspace.org_unit_path` (default `/`)
  - Scope: `admin.directory.user`.
- **Contract:** `App\Contracts\Services\WorkspaceDirectoryInterface`:
  - `find(string $email): ?WorkspaceUser` (aliases count as taken)
  - `create(WorkspaceUserData): WorkspaceUser`
  - `suspend()` / `unsuspend()` / `rename()`
  - It is implemented by `App\Services\Google\GoogleWorkspaceDirectory` on `Google\Service\Directory`, which is already installed with `google/apiclient-services`, so there's no new dependency.
  - `Tests\Fakes\FakeWorkspaceDirectory` stands in for it in tests.
- **Choosing an address:** `MemberIdentityService::allocateOrgEmail()`.
  - Candidates are deterministic: `first.last`, then `first.last2`, `first.last3`, … This replaces `rand(1,1000)`.
  - A candidate is free only if no User (including trashed) has it, no Member in any tenant has it, and `WorkspaceDirectory::find()` returns null.
  - The unique index on `users.email` acts as the reservation lock: the local User is created inside the transaction, and a unique violation moves on to the next candidate.
- **Provisioning flow:**
  1. `Member/CreateJob` fires `MemberCreated`.
  2. A queued listener, `Member\ProvisionWorkspaceAccount`, runs on queue `high`, is `ShouldBeUnique` per member, and retries with backoff.
  3. It creates the Workspace user with a random per-user temporary password and `changePasswordAtNextLogin = true`.
  4. **If Workspace returns 409 (conflict)**, it allocates the next free candidate, updates the member's and user's email through model updates (so events fire), and retries.
  5. On success it sets `members.workspace_user_id`, `workspace_status = PROVISIONED` and `workspace_provisioned_at`, then sends `MemberCredentialsIssuedNotification` **with `sendNow`**, so the temporary password is never written to the queue table. The email goes to `personal_email`.
- **Lifecycle:**
  - Deleting a member suspends the Workspace user (it is never deleted). Restoring the member unsuspends it.
  - A name change renames the Workspace user. The email address never changes after it's provisioned.
- **Migration on `members`:** `workspace_user_id`, `workspace_status` (new enum `PRFWorkspaceStatus`: `NOT_APPLICABLE`, `PENDING`, `PROVISIONED`, `FAILED`, `SUSPENDED`), `workspace_error`, `workspace_provisioned_at`.
- **Filament:**
  - A Workspace status badge and filter.
  - "Retry Workspace provisioning".
  - "Link existing Workspace account", for mailboxes someone already created by hand.
  - The shared `google_workspace_temp_password` setting, the `GmailExport` CSV and `InviteMembersCommand` are removed, because provisioning replaces them.
- **Backfill:** `prf:members:sync-workspace {--tenant=} {--dry-run}`. For each existing org-mode member it:
  - links the member to the Workspace account if the address already exists there;
  - creates the account if it doesn't;
  - reports conflicts (the address belongs to someone else, or a name mismatch) for you to resolve.
- **Tests** (with the fake directory):
  - provisioning succeeds;
  - a Workspace 409 moves to the next candidate;
  - an address held only in Workspace is skipped when allocating;
  - the temporary password is never in the queue payload;
  - delete suspends and restore unsuspends;
  - missing Workspace config fails closed and marks the member FAILED;
  - the backfill dry run.

**Filament Members** (`MemberResource.php` and its pages, table and actions)
- **Form:**
  - "System Email" is shown only in org mode (read-only).
  - In personal mode, `personal_email` is labelled "Email (used to sign in)".
  - `personal_email` gets a tenant-scoped unique rule, and the create and update validation agree.
- **Table:** the Contact column and its search cover `email` and `personal_email`. Fix the tooltip, which says "Personal email" but shows the system email.
- **Actions:** the Workspace actions described above appear only in org mode. Personal mode gets a "Send sign-in invite" action instead: an email saying "Sign in with Google using this address", with no password. Remove or finish the "Send Invites" bulk stub.
- **API:** `Member/CreateRequest` and `UpdateRequest` apply the same rules. `personal_email` is required, and `nullable` is removed to match the NOT NULL column. In personal mode, `Member\Resource` hides `email` behind the same sensitive-info check as `personal_email`.

**Tenant creation** (the central `TenantResource`, `CreateTenant` page, `tenants:create` command, `CreateTenantAction`, `ProvisionTenantJob`)
- New inputs:
  - **"How do members sign in?"**: Organisation Google Workspace domain, or Personal email.
  - **Org email domain**: required only in org mode. Validated as a domain and rejected if it's public webmail.
  - Admin email and custom domain, validated for format and uniqueness.
- `ProvisionTenantJob`:
  - saves the mode and domain from the input. It no longer uses `domains->first()`.
  - stops logging `admin_password`.
  - sends the admin a password-reset link instead of an unusable hash.
- `WelcomeNotification`: fix the malformed tenant URL (build it from the tenant's primary domain), and include the sign-in instructions for the chosen mode.
- The `data` and `is_active` inputs, which are currently ignored, are passed through `CreateTenantAction`.

**Fixing existing data.** `prf:members:repair-emails {--tenant=} {--dry-run}` does the following:
- For tenants whose domain is blank, `gmail.com` or the hosting domain, it switches the tenant to PERSONAL.
- It moves `members.email` and `users.email` to `personal_email` where that's safe.
- It prints a report of conflicts (the address already belongs to another User) for manual resolution.
- It's idempotent, and you run it per tenant after reviewing the dry run.

**Tests** (`tests/Feature/Members/`, `tests/Feature/Tenancy/`):
- Member creation in both modes, including that a public-webmail domain is never generated.
- The same personal Gmail as a member in two tenants: one User, attached to both.
- Deleting a member in one tenant keeps their access in the other.
- `canAccessPanel`: a member is denied, a desk role is allowed.
- A Google login for a user from another tenant is denied.
- Tenant provisioning saves the mode and domain from the input.
- The repair command's dry run and conflict report.

---

## Phase 4: Tenant-owned integrations (Paystack, SMS, FCM, AI), fail closed
**Foundation**
- `App\Enums\PRFIntegration`: `PAYSTACK`, `SMS`, `FCM`, `AI`, and `GOOGLE_WORKSPACE` (required only in org-domain mode; see Phase 3). Each case knows its required keys, which keys are secret, and which config keys it fills.
- `App\Services\Tenancy\TenantIntegrations` with `isConfigured()`, `require()`, `load()` and `reset()`.
- `App\Exceptions\IntegrationNotConfiguredException`. Over the API it returns a 422 with a clear message. Inside a job it logs and skips.
- In `AppServiceProvider`, delete `loadSafeDefaults()` and all 56 `env()` calls, which break under `config:cache`.
- Loading moves to `TenancyServiceProvider`, after `BootstrapTenancy`:
  - `TenancyInitialized` calls `load()`. There is no env fallback: a missing value becomes `null`.
  - `TenancyEnded` calls `reset()`.
  - Platform integrations (NLP, Azure, weather, Maps, Sheets, Drive, Turnstile, mail) read only from `config/prf/*`. Fix the Africa's Talking config key path.
- **AppSetting:**
  - Secret values are encrypted with a cast that uses `PRFIntegration`. A migration encrypts the values already stored.
  - Settings are masked in Filament, and uniqueness is checked per tenant.
  - Uses the new `AppSettingPolicy`.
  - A banner lists the integrations that aren't configured yet.
  - The seeder creates every per-tenant key as `''`.

**Paystack**
- **Webhook route:** `POST v1/paystack/{tenant}/ipn`, using `InitializeTenancyByPath` and `VerifyPaystackSignature`. The middleware gets the gateway injected through its constructor.
- **Controller:** `notifyPayment` returns a typed `JsonResponse`. Settings show each tenant the URL to use.
- **Per-tenant keys:** secret, public, callback URL and currency (`KES` by default, sent when a transaction is initialised). Remove the hard-coded callback URL from `config/prf/payments.php`.

**SMS: provider-agnostic**
- **Base class:** `App\Services\SMS\SMSGateway` (abstract) handles, once for all drivers:
  - E164 normalisation, with the region from config
  - the non-production test-number override
  - creating and updating the `SMSLog`
  - timeouts and `$response->failed()` checks
- **Drivers** implement only `deliver(string $e164, string $message): SMSResult`. `SMSResult` is a readonly DTO: `messageId`, `status`, `cost`, `raw`.
- **Manager:** `SMSManager` extends `Manager`. Its default driver is the tenant's `sms.driver`, and `require(SMS)` runs on every send. A new provider is one driver class plus a `createXDriver()` method (or `SMSManager::extend()`); the guide documents how.
- **Interface:** `SMSGatewayInterface` becomes `send()` and `deliveryStatus(string $messageId)`. The Advanta-only `checkBlacklist` goes away.
- **Migration:** add `provider`, `status`, `cost` and `delivered_at` to `sms_logs`.

**FCM**
- Rebind `Kreait\Firebase\Contract\Messaging` to the tenant factory, and remove its fallback to the env credentials file.
- A `RoutesToFcm` notification trait adds the FCM channel only when `isConfigured(FCM)`.

**AI: provider- and model-agnostic through `laravel/ai`**
- `composer require laravel/ai`. Before writing code, check its API with `search-docs` and `vendor/laravel/ai`.
- **Interface:** redesign `AIServiceInterface` around two calls:
  - `text(AiPrompt $prompt): string`
  - `structured(AiPrompt $prompt, array $schema): array`
- **Prompt DTO:** `AiPrompt` holds the system prompt, user prompt, feature and max tokens.
- **Implementation:** `App\Services\AI\LaravelAiService` calls the SDK. The provider, model and key come from tenant settings: `ai.provider`, `ai.model`, `ai.api_key`. Optional per-feature models: `ai.models.{feature}`, for example `executive_summary`.
- **Prompts** move out of inline heredocs into `app/AI/Prompts/{Feature}Prompt.php` classes: MissionWeatherRecommendations, ExecutiveSummary, SocialMediaCaptions.
- **Consumers:** migrate all 4 (the Mission and PRFEvent weather jobs, `GenerateExecutiveSummaryJob`, `SendToSocialMediaJob`) and delete their private `runPrompt()` methods and the `sleep(6)`/`sleep(2)` calls.
- **Tests:** rewrite `tests/Services/Service{Binding,Implementation,Swap}Test.php` to use the SDK's fakes.
- NLP (own RAG service) and Azure STT remain platform services. Move the Azure response parsing out of `PollTranscriptStatusJob` into the service.

**Scheduled commands:** add a `RunsForEachTenant` trait built on `tenancy()->runForMultiple()`. Use it in every command that touches tenant data: pledges, payments, weather, and the existing `FillBudgetSummaries` / `RepairMemberUserLinks`.

**Tests:**
- Keys are isolated between two tenants.
- A missing key returns 422.
- Config is reset when tenancy ends.
- Webhook: a valid signature is accepted, the wrong tenant's secret gets 403, and an unknown tenant gets 404.
- FCM is skipped when it isn't configured.
- A second SMS driver can be swapped in through `extend()`.
- The AI provider can be swapped using fakes.

---

## Phase 5: Uniform jobs
**Four job shapes, each with a template in `prf-jobs-and-side-effects.md`:**

| Kind | Shape | Example |
|---|---|---|
| **CRUD** (sync) | `CreateJob(array $data): Model` and `UpdateJob(array $data, string $ulid): Model`. Load with `firstOrFail`, then `->update()`, then return the model. `@param array{…}` shape. ULID→ID conversion inside. `DB::transaction` when there are several writes. | `Department/UpdateJob` |
| **Action** (sync state transition) | `{Verb}Job(Model $model, User $actor, array $data = []): Model`. Runs in a transaction, checks the transition is allowed, writes, then dispatches a domain event. | `Mission/ApproveJob`, `Requisition/RecallJob`, new `Mission/CompleteJob` (replaces `MissionCompletionService::completeMission`) |
| **Work** (queued, external I/O or heavy) | `implements ShouldQueue`, `use Queueable`, model constructor. Laravel 13 queue attributes: `#[Queue('long')]`, `#[Tries(3)]`, `#[Backoff([30,120,300])]`, `#[Timeout(…)]`. `failed()` logs. `ShouldBeUnique` always with a `uniqueId()`. | `Mission/GenerateExecutiveSummaryJob`, `SMS/SendSMSJob` |
| **Recalculate** (queued derived data) | `Recalculate{X}Job`. Idempotent. | `CourseMember/RecalculateProgressJob` (renamed from `NotifyProgressJob`) |

- **Hard rule: jobs never use query-level writes.**
  - Jobs, listeners and actions never call `update()`, `delete()`, `increment()`/`decrement()`, `forceDelete()` or `restore()` on an Eloquent `Builder` or `Relation`.
  - They load the model and call the method on it, so observers, domain events and the activity log always run.
  - For sets of rows, use `->lazyById()->each(fn (X $x) => $x->update([...]))`.
  - The only way to skip side effects is `updateQuietly()`/`saveQuietly()` on a model, with a PHPDoc line on the job saying why.
  - **Enforced by a custom PHPStan rule**, `phpstan/Rules/NoQueryBuilderWritesRule.php`. It is type-aware through Larastan, applies to `App\Jobs`, `App\Listeners` and `App\Actions`, and is registered in `phpstan.neon`.
  - **Current violations to convert:**
    - the 22 UpdateJobs
    - `MissionSubscription/MarkConflictsJob`
    - `Mission/NotifyWhatsAppGroupJob`
    - `Requisition/RequestReviewJob`
    - `Mission/UpdateJob`, `PRFEvent/UpdateJob`, `School/UpdateJob`, `MissionSubscription/UpdateJob`
    - `RequisitionItemObserver`: the requisition total becomes a model update
    - `MemberObserver`: the user rename becomes a model update
- **Bring all 159 jobs in line with these shapes:**
  - Standardise the argument order to `(data, ulid)`. 5 jobs are currently reversed.
  - Always name the parameter `$ulid`.
  - Convert the 22 query-level UpdateJobs. For each, diff its validated keys against `#[Fillable]` first.
  - Replace the 13 `->first()->id` lookups with `firstOrFail`.
  - Pass model constructor arguments instead of the `int $id` ones.
  - Drop the "Create a new job instance" boilerplate.
  - Remove `auth()` from inside jobs; the actor is passed in instead.
  - Status changes that come through a CRUD `UpdateJob` are handed to the matching Action job, so the API keeps working and events still fire.
- **Merge duplicates:**
  - Mission and PRFEvent implement `App\Contracts\Forecastable`.
  - One `Weather/GenerateForecastJob` and one `Weather/GenerateRecommendationsJob` replace the 4 copies. One command replaces the 2.
  - `AccountingEvent/CreateForEventJob(Mission|PRFEvent)` replaces the 2 `CreateAccountingEventJob`s.
- **Jobs that only send a notification are removed** (`Notify*Job`, `SendThankYouJob` and similar); listeners take over in Phase 6.
- **Queue config:**
  - `after_commit => true` on every connection.
  - `retry_after` 660, which is more than the largest job timeout of 600.
  - Named queues: `high` (payments, notifications), `default`, `long` (media, AI).
  - `.fly/start-queue.sh` uses `--queue=high,default,long --timeout=620`. Fix the numprocs comment.
  - `CreateCohortJob` and `ProvisionTenantJob` become explicitly sync, because they are always `dispatchSync`'d.

---

## Phase 6: Side-effect architecture (domain events and listeners)
**Rules** (in `prf-jobs-and-side-effects.md`):
1. **Writes happen in jobs.** A CRUD or Action job that changes meaningful state dispatches `App\Events\{Model}\{Model}{PastTense}`, for example `Mission\MissionApproved`. Domain events implement `ShouldDispatchAfterCommit` and carry the model.
2. **Each listener does one thing.** Listeners are `App\Listeners\{Model}\{Verb}{Object}`, for example `Mission\NotifyMembersOfApprovedMission` or `Mission\ScheduleWeatherForecast`. They are `ShouldQueue` and `ShouldHandleEventsAfterCommit`, found by auto-discovery, and must be idempotent.
3. **Observers only protect the model's own consistency:** derived fields, cache clearing, slugs, owned cascades (e.g. Member→User soft delete). No notifications, no queued jobs, no external I/O.
4. **No query-level `update()` on models that have events.** `updateQuietly` is only for an observer writing back to its own model.
5. **Filament actions and bulk actions call the same Action jobs as the API.** This fixes the requisition bulk-approve ledger gap and the mission approve/reject paths.
6. **Broadcasts** (`ShouldBroadcast`) are dispatched from listeners and keep the `PRFLiveEvent` payload shape.
7. **Tests** use `Event::fake([...])` on jobs, and test listeners on their own with `Notification::fake()` / `Queue::fake()`.

**Migration map** (built from the side-effect audit):
- **`MissionObserver` status switch.** Each status gets its own event and listeners, replacing the switch:
  - `MissionApproved`: create the accounting event, weather, notify the school by SMS, notify members.
  - `MissionServiced`: request feedback, summary, financial report, thank-you, cohort.
  - `MissionPostponed` and `MissionCancelled`.
  - `MissionWhatsAppGroupLinked`.
  - Fix the `accountingEvent` null crash.
- **`MissionSubscription`:**
  - `MissionSubscriptionCreated`: notify the member, notify the desk (Filament now does this too), identify conflicts.
  - `MissionSubscriptionStatusChanged`: mark conflicts, then notify. Use a proper `Bus::chain`.
  - Conflicted members now get notified.
- **Requisitions:** `RequisitionApproved`, `RequisitionRejected`, `RequisitionRecalled` (notification after commit) and `RequisitionReviewRequested`. Take the inline notifications out of the jobs.
- **Other events:**
  - `PRFEventCreated`/`PRFEventLocationChanged`
  - `SchoolLocationChanged` (route calculation)
  - `StudentEnquiryCreated`/`StudentEnquiryReplyCreated` (chatbot, participants)
  - `PrayerRequestCreated`, `MissionGroundSuggestionCreated`, `EventSubscriptionCreated`
  - `PaymentSucceeded` (reconcile the pledge)
- **`MemberObserver.created`.** The user creation, role and group work moves into `Member/CreateJob`. Only the `full_name` invariant stays in the observer, so imports and seeders go through the job as well.
- **Learning-progress cascade.** Replace it with one `RecalculateProgressJob` chain, triggered by a `LessonMemberCompleted` event.

---

## Phase 7: Notifications, labelled by model
- **Naming:** `app/Notifications/{Model}/{Model}{Event}Notification.php`, where the folder is the model the constructor takes. Rename the classes to match:
  - Mission: `MissionApproved` (was NewMission), `MissionCancelled`, `MissionPostponed`, `MissionServiced` (was ThankYou), `MissionWhatsAppGroupLinked`
  - AccountingEvent: `AccountingEventCreated` (merges the two `CreateRequisitionNotification` copies), `AccountingEventFinancialsReady`
  - Requisition: `RequisitionApproved`, `RequisitionRejected`, `RequisitionRecalled`, `RequisitionReviewRequested`
  - MissionSubscription: `MissionSubscriptionCreated` (for the member) and `MissionSubscriptionReceived` (for the desk)
  - Other: `PRFEventCreated`, `EventSubscriptionCreated`, `PrayerRequestReceived`, `MissionGroundSuggestionReceived`, `StudentEnquiryCreated`, `StudentEnquiryReplyCreated`, `PledgeDue`, `MemberCredentialsIssued`, `TenantProvisioned`
  - Reports: `MissionExecutiveSummariesReport`
- **Base class:** `App\Notifications\BaseNotification` (abstract).
  - Implements `ShouldQueue` and runs on the `high` queue.
  - `via()` returns mail plus the `RoutesToFcm` trait.
  - Its `type()` returns a `PRFNotificationType` enum value (`mission_approved`, …), used as the FCM `type`.
  - Its `targetApp()` covers the MISSIONS, LEADERSHIP and STUDENTS apps.
  - Subclasses implement only `toMail()` and `fcmTitle()`/`fcmBody()`.
  - Remove the empty `toArray()` stubs.
- **Desk recipients:** `Notification::route('mail', Utils::getDeskEmails(PRFResponsibleDesk::X))`. Today desk addresses that aren't Member emails never receive anything. Read desk emails in one place only (`Utils::getDeskEmails`). Put the bit.ly links in config.
- **Mobile apps:** the FCM `type` strings change, so the Missions and Leadership apps need updating. The guide will list an old→new mapping. **Alternative:** `PRFNotificationType` keeps the legacy strings as values until the apps ship. Recommend doing that; the change stays backward compatible.
- `NewStudentEnquiryNotification` currently delivers nothing (`via()` returns `[]`). Keep that behaviour, but stop queuing it for every member. Flag it for your decision.

---

## Phase 8: Payment status polling that scales
- **Webhook first.** The per-tenant webhook (Phase 4) is the main way a payment's status changes. Polling is a bounded fallback.
- **Migration on `payments`:** add `status_checked_at`, `status_check_count` and `next_status_check_at`, plus an index on `(tenant_id, payment_status, next_status_check_at)`. Add a `PRFPaymentStatus::EXPIRED` case.
- **One state machine** in `Payment/ApplyGatewayStatusJob` (Action type):
  - It locks the row with `lockForUpdate`.
  - A terminal status (SUCCESS, FAILED, CANCELLED, EXPIRED) is never changed, except FAILED/CANCELLED → SUCCESS when the gateway reports success.
  - Paystack's `ongoing`, `pending`, `processing` and `queued` statuses keep the payment INITIALISED.
  - `abandoned` becomes CANCELLED only after a 30-minute checkout window.
  - Success dispatches `PaymentSucceeded`.
  - The webhook, the `/check-status` endpoint and the poller all use this job.
- **Poller:** `prf:payments:poll-status` runs every 5 minutes, with `withoutOverlapping` and `onOneServer`.
  - It runs for each tenant.
  - It uses `chunkById` over payments that are due for a check and are less than 24h old.
  - It dispatches a queued `Payment/VerifyStatusJob` on `#[Queue('high')]`, with `ShouldBeUnique` keyed on the payment id and the `RateLimited('paystack')` middleware.
  - Backoff between checks: 2, 5, 10, 30, then 60 minutes.
  - Payments older than 24h are marked EXPIRED in one query.
- **Stuck PENDING rows.** If a transaction fails to initialise, `CreateJob` catches it and marks the payment FAILED, so PENDING rows no longer get stuck.
- **Tests:** backoff scheduling, the no-downgrade guard, expiry, the unique job, webhook and poller racing each other, and one tenant's keys per job.

---

## Phase 9: Models, data layer, HTTP, Filament and console uniformity
- **Attributes:**
  - `#[Fillable]`, `#[Hidden]`, `#[Appends]` and `#[Table]` on the 84 plain models, plus Tenant and User.
  - Leave the Role, Permission, Media, Activity and PersonalAccessToken parents alone.
  - Single-class `#[ObservedBy(X::class)]`.
  - 14 local scopes become `#[Scope]`.
  - Keep `HasCrossDomainConnection` and the `@use HasFactory<X>` generics on all 62 models.
  - Larastan's `$appends` check no longer applies. Accepted.
- **Models:**
  - 8 `$casts` properties become `casts()`.
  - 178 relation methods get return types.
  - 13 old-style accessors become `Attribute::make`.
  - 25 bare constants become `public const`.
  - Add `HasQueryBuilderCapabilities` and `SORTS` to the 9 models missing them.
  - Rename the `mmemberModules` typo.
  - Add a `Member::currentId()` helper to replace 9 copies of the same subquery.
  - Remove the Mission count accessors from `$appends` and use `withCount`/`whenCounted` instead (N+1).
- **Migrations:**
  - Pledge tables get a `tenant_id` FK and NOT NULL.
  - Add unique indexes on `ulid` for `mission_sessions` and `payment_types`.
  - Pledge amounts become integer KES.
- **Factories:** add the 14 missing ones, use `$this->faker` everywhere, and create related records with `X::factory()`.
- **HTTP:**
  - `(Request, string $ulid)` parameter order.
  - `store()` returns 201.
  - Remove the 26 wrong docblocks and the unused pre-fetches.
  - Add return types.
  - Rules use array syntax (61 files).
  - Resources: kebab-case `entity` in 10 files. Other domains' resources are referenced by their fully qualified `\App\Http\Resources\{Domain}\Resource`, never aliased; convert the 10 aliased files.
- **Services and console:**
  - Inject `GoogleSheetsInterface` and `GoogleDriveInterface`, and move them to `Services/Google/`.
  - Constructor injection instead of 29 `app()` calls.
  - Commands use the `prf:{domain}:{action}` naming and `handle(): int`, and the schedule comments are corrected.
  - Add a `PRFRole` enum for the `'super admin'` strings.
  - Move the personal phone number into env.
- **Out of scope, reported only:**
  - Filament date formats and `Select::make` cleanup
  - the central Filament resource layout
  - `strict_types`
  - re-enabling Turnstile (it has a TODO)

---

## Phase 10: Tests (simple and repeatable)
- **Layout:**
  - `tests/Feature/Api/{Domain}Test.php`: move about 28 CRUD tests here from `tests/Unit`.
  - `tests/Feature/{Tenancy,Webhooks,Filament,Listeners,Jobs}/`
  - `tests/Unit` keeps only real unit tests.
- **`Pest.php`:** one Feature setup. `actingAsTenantUser([])` gives a user with no roles, for 403 tests. Remove the boilerplate.
- **Template:** every API test file has `describe` blocks for `index`, `store`, `show`, `update` and `destroy`, each covering:
  - the happy path
  - a validation dataset (`->with([...])` and `assertJsonValidationErrors`)
  - `assertForbidden`
  - `assertSoftDeleted` on destroy
  Use present-tense names and `route()` only. Convert `SocialstreamRegistrationTest` to Pest.
- **Job and listener tests:** each Action job asserts its event with `Event::fake`. Each listener gets a small test with `Notification::fake`.
- **Tests run in Docker** (`Dockerfile.test` and `docker-compose.test.yml`, on Postgres 16 with RLS as the RLS user):
  - Makefile `test`: run the whole suite (`php artisan test`) instead of only `tests/Unit`.
  - New `make test-filter FILTER=…` and `make test-file FILE=tests/Feature/Api/DepartmentTest.php` targets for quick runs inside the container.
  - New `make stan` target without `--fix`.
  - `Dockerfile.test` moves to `php:8.4-cli` to match CI and production. Its own comment already says "PHP 8.4". Add any PHP extensions the new code needs; `laravel/ai` and the Google Directory API need none beyond curl, json and mbstring.
  - The container mounts the repo and uses the host's `vendor/`, so run `composer install` on the host after adding `laravel/ai`.
  - Tests should never depend on host services: fakes cover Workspace, AI, Paystack (`Http::fake`) and SMS.
  - `QUEUE_CONNECTION=sync` in the container, and `RefreshDatabase` makes after-commit events and listeners run inside tests. Assert on them with `Event::fake`/`Queue::fake` where the side effect isn't what's being tested.
- **CI:** `test-code.yml` runs the same full suite plus `phpstan analyse`, including the new `NoQueryBuilderWritesRule`.

---

## Phase 11: Developer docs and seeded environment
- **Seeder fixes:**
  - The seeded default tenant uses `ORGANISATION_DOMAIN` mode with `org_email_domain = example.org` outside production. `example.org` is reserved for examples by RFC 2606. `getOrgEmailDomain()` no longer falls back to `gmail.com` (see Phase 3).
  - Seeded users are attached to the tenant through `tenant_user`. Leadership roles get `access tenant panel`.
  - Optional: seed a second demo tenant in PERSONAL mode so both modes can be tried.
  - Add fixed accounts `member@{domain}` (for Missions) and `student@{domain}`.
  - `UserSeeder` prints the **plain** password outside production; today it prints the bcrypt hash.
- **`docs/developer-invite.md`:**
  - Stack: Laravel 13, PHP 8.4, Filament 5, Livewire 4, Pest 5, Postgres with RLS, multi-tenant.
  - Fix links: `../README.md`, `../LICENSE`, `./acknowledgements.md`.
  - Credentials table with each app's account (`admin@`, `chairperson@`, `member@`, `students@` at `example.org`) and its password: `1password` locally, `asZDcVt7Q` for demo and staging.
  - API client signing: the seeded app IDs, and where to find each app's secret (Filament → System Settings → API Clients).
  - Per-tenant integrations are set up in App Settings; list which ones fail closed.
  - Update the environment disclaimer.
- **`docs/system-access.md`:** same credentials, so the two docs don't disagree.
- These passwords already live in `Utils::randomPassword()` and apply outside production only. Production credentials are random and never documented.

---

---

## Phase 12: Every test passes
The Feature and Services suites have never run in CI. On `main`, 75 tests fail (baseline taken 2026-09-25 with `make test`). After Phases 1–11, fix every failing test, whether the test or the code is at fault. Examples:
- a missing Vite manifest in the test image
- rate-limit state left in the file cache
- the Jetstream-era auth tests

When this phase is done, `make test` is fully green and CI runs the whole suite.

## Verification
- **All tests run in the Docker test environment**, not on the host:
  - While working, run `make test-filter FILTER=…` or `make test-file FILE=…`.
  - At the end of each phase, run `make test`: the full suite on Postgres + RLS, the same as CI.
  - Run `make test-build` once first, and again after `Dockerfile.test` changes.
- `vendor/bin/mago fmt`, and `vendor/bin/phpstan analyse --memory-limit=2G` at level 10 with no `--fix`.
- `php artisan route:list --path=api` for the v2 middleware and the Paystack `{tenant}` route. `php artisan event:list` to confirm each listener is registered once.
- **Manual checks:**
  - Two tenants with different Paystack test keys: initialise a payment on each, then send signed webhooks to each tenant's URL.
  - Switch the AI provider in App Settings and regenerate an executive summary.
  - Create a member on an org-domain tenant and on a personal-email tenant.
- After `boost:update`, the agent files contain no `pint`, say Laravel v13, and have one copy of the guide each.
- Run `migrate:fresh --seed` and log in with every account listed in `developer-invite.md`.
