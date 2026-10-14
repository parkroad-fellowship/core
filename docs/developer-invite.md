# Open Source Announcement + Developer Invite

Greetings everyone,

Since our development journey began in 2024, the Parkroad Fellowship app ecosystem has grown into a robust suite of digital tools built to support missions, leadership operations, discipleship and accountability.

Today, we are happy to share this as one combined update:

1. We are open source.
2. We are inviting developers to try the platform to see if it would meet your needs and allow you to fork and customise.

By opening the codebase, we hope to provide engineering inspiration for fellow developers and make customisation possible for changing ministry and fellowship needs in the world.

---

## The Repositories

| App | Purpose | Repository |
|---|---|---|
| **PRF Core** | Backend API and admin panel powering the entire ecosystem | [https://github.com/parkroad-fellowship/core](https://github.com/parkroad-fellowship/core) |
| **PRF Missions** | Mobile app for field team members | [https://github.com/parkroad-fellowship/missions](https://github.com/parkroad-fellowship/missions) |
| **PRF Leadership** | Mobile app for leaders and the mission desk | [https://github.com/parkroad-fellowship/leadership](https://github.com/parkroad-fellowship/leadership) |
| **PRF Design** | Brand assets and design guidelines | [https://github.com/parkroad-fellowship/prf_design](https://github.com/parkroad-fellowship/prf_design) |

Feel free to fork the repositories, open issues and submit pull requests. Let us build better fellowship systems together.

## What the Platform Covers

Mission planning, member management, financial workflows (requisitions, approvals, allocation entries, Paystack payments and pledges), discipleship and courses, prayer, events and a full admin panel, all backed by a versioned REST API. The platform is **multi-tenant**: one deployment serves many fellowships, each with its own data, settings and integration accounts.

This is a developer-first release. There are no click-through demo builds. To experience the system, run the code locally or point the staging/development app flavours at the demo environment.

## Technology Stack

- **Backend API:** Laravel 13 on PHP 8.4
- **Admin panel:** Filament 5 (Livewire 4, Tailwind CSS 4)
- **Database:** PostgreSQL 16 with row-level security for tenant isolation
- **Multi-tenancy:** stancl/tenancy (one database, `tenant_id` on every tenant table)
- **Realtime and async:** queues (`high`, `default`, `long`) and WebSockets (Laravel Reverb)
- **AI:** Laravel AI SDK; each fellowship brings its own provider (for example Azure AI Foundry with DeepSeek)
- **Integrations:** Paystack, SMS (Advanta, Africa's Talking), Firebase Cloud Messaging, Google Workspace
- **Mobile apps:** Flutter (PRF Missions and PRF Leadership)
- **Quality:** Pest 5 tests (run in Docker), PHPStan level 10, Mago formatter
- **Deployment:** local native setup, Docker Compose or Kamal

---

## Running Locally

### PRF Core (Backend API)

Full setup instructions are in the [README](../README.md). In short:

```bash
composer install && bun install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed     # creates the demo fellowship and the accounts below
                                     # (also runs tenants:sync-rls; run it by hand if you
                                     #  ever migrate and seed separately)
make test-build && make test         # full test suite in Docker (Postgres + RLS)
```

The seed prints the password it used when it finishes.

### Mobile Apps (Missions and Leadership)

Clone the relevant app repository, set the API base URL in the staging/development flavour configuration to your local or our demo environment, and run a standard Flutter build.

If you just want to try the mobile experience without building from source, use the staging/development builds from Firebase App Distribution:

| App | Flavour | Link |
|---|---|---|
| PRF Leadership | Staging / Demo | https://appdistribution.firebase.dev/i/0d8820b0a1f93d92 |
| PRF Missions | Staging / Demo | https://appdistribution.firebase.dev/i/0d8820b0a1f93d92 |

You will need to request access through the Firebase App Distribution link. Follow the on-screen prompt to register your device.

---

## Seeded Accounts

Seeding creates one demo fellowship whose members sign in with organisation addresses on `example.org`, a domain reserved for documentation that can never reach a real inbox.

| Where to sign in | Email | Role |
|---|---|---|
| Fellowship admin panel (`/admin` on the fellowship's domain) | `admin@example.org` | Super admin |
| PRF Leadership app | `chairperson@example.org` | Chairperson (also `vicechair@`, `treasurer@`, `missions@`, `organizingsec@`, `prayerdesk@` and `committee@` for the other desks) |
| PRF Missions app | `member@example.org` | Member |
| Students app | `students@example.org` | Student |
| Central panel (`/admin` on the central domain, e.g. `prf.test`) | `engineering@parkroadfellowship.org` | Platform super admin, manages all fellowships (created only when seeding without a tenant) |

**Passwords**

| Environment | Password |
|---|---|
| Your local machine (`APP_ENV=local`) | `1password` |
| Hosted demo, staging and development | `asZDcVt7Q` |
| Production | Random, never documented. Email/password sign-in is disabled there. |

These are shared, non-production credentials. Never reuse them anywhere real.

**Hosted demo:** [https://demo.parkroadfellowship.org](https://demo.parkroadfellowship.org) (pull requests deploy to [https://dev-app.parkroadfellowship.org](https://dev-app.parkroadfellowship.org))

## Calling the API from Your Own Client

Once API clients exist (seeding creates them), every API request must be signed:

- `X-PRF-App-ID`: the client's app ID (`prf_missions_…`, `prf_leadership_…`, `prf_students_…`)
- `X-PRF-Timestamp`: the current time in **milliseconds**, valid for ±30 seconds
- `X-PRF-Signature`: an HMAC of the method, the full URL, the timestamp and the app ID, keyed with the client's secret (see `App\Helpers\RequestSigner`)

App IDs are fixed by the seeder. Secrets are random, so read them from **Admin panel → System Settings → API Clients**. Each app ID only admits the roles it is meant for: Missions admits members, Leadership admits desk and committee roles, Students admits students.

## Integrations Are Configured per Fellowship

Each fellowship enters its own credentials in **Admin panel → Settings → App Settings**. They are stored encrypted, and there is no shared fallback. Until a fellowship configures an integration, the features that need it are switched off: the API answers with a clear 422 and background work skips quietly.

| Integration | Needed for |
|---|---|
| Paystack | Taking payments and pledges. The App Settings page shows the webhook URL to paste into Paystack. |
| SMS (Advanta or Africa's Talking) | Messages to schools and patrons |
| Firebase | Push notifications to the mobile apps |
| AI (Azure AI Foundry, OpenAI, Anthropic, Gemini, …) | Executive summaries, weather advice, social media captions |
| Google Workspace | Creating member mailboxes automatically (organisation-domain fellowships only) |

Maps, weather, speech-to-text, mail and storage are platform services configured in `.env`.

## Environment Disclaimer

The demo fellowship has no integration credentials by default, so payments, SMS, push notifications, AI features and Workspace mailboxes are switched off there until you add your own test keys in App Settings. Email sending and Google Maps may also be unavailable in shared environments to prevent abuse.

---

## What to Explore First

- The admin panel: every domain (missions, finance, members, courses, prayer, events) in one place
- The mission workflow API: the full lifecycle from creation through approval to completion, with domain events driving the notifications
- The financial flows: requisitions with multi-step approvals, allocation entries, Paystack webhooks per fellowship
- The treasurer's workspace (Admin panel → Treasurer): the cashbook per account, receipting offline income with instant PDF/SMS/WhatsApp receipts, monthly accountability for every desk, and Excel workbooks laid out like the treasurer's own books
- The v1 and v2 API routes: versioned, Sanctum auth, Spatie QueryBuilder filtering, sorting and includes
- `AGENTS.md` and `.ai/guidelines/`: the conventions every change follows

---

## How to Contribute

Fork any repo, open issues, or submit pull requests. Contributions are welcomed under the terms of the [Parkroad Fellowship Public Ministry Licence](../LICENSE).

If you are building something on top of the PRF stack, we would love to hear about it. Drop us a message at engineering@parkroadfellowship.org.

---

The PRF ecosystem is made possible by generous sponsorships and free tiers from a number of organisations. See our [Acknowledgements](./acknowledgements.md) for the full list.

Best regards,
**Parkroad Fellowship Engineering**
