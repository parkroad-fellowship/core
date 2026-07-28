# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

**Fellowship Leadership** — the primary operators of the admin panel. Roles map to navigation groups in the Filament sidebar:

- **Organising Secretary** — manages members, groups, memberships, and class groups
- **Missions Secretary** — plans and executes missions, manages schools, mission types, FAQs, and ground suggestions
- **Follow-Up Secretary** — oversees cohorts, courses, lessons, modules, student enquiries, and letters
- **Prayer Secretary** — manages prayer prompts, prayer requests, and spiritual life tracking
- **Treasurer** — handles accounting events, requisitions, payments, budget estimates, expense categories, and transfer rates
- **E-Learning Admin** — manages courses, modules, lessons, and course members

**System Administrator** — a technical user who manages tenants (via the Central panel), app settings, API clients, and user accounts. Handles configuration, integrations, and system-level operations.

**Field Teams** — missioners who subscribe to missions, track activities, and submit reports. They interact through separate mobile apps (Android, iOS, Huawei) that consume the same Laravel API, not through the Filament admin panel.

## Product Purpose

PRF Core is the backend API and admin platform for Parkroad Fellowship's evangelistic ministry to secondary schools across Kenya. It connects leadership, field teams, finance officers and students — before, during and after every mission.

The platform makes it possible to plan a full mission lifecycle (school selection → team assignment → execution → debrief → student follow-up → financial accountability) in a single system, replacing spreadsheets, manual WhatsApp coordination and disconnected tools.

Success means every mission is planned with clear budgets, executed with informed teams, followed up with students through cohorts, and accounted for with transparent financial records.

## Positioning

PRF Core is purpose-built for a specific ministry workflow — mission lifecycle management with integrated student follow-up and financial accountability — that generic church management tools, CRMs or project management software do not cover as a unified system.

The multi-tenant architecture means each fellowship branch or partner organisation operates independently with its own data, users and configuration, while sharing the same platform infrastructure.

## Operating Context

- **Admin panel** is accessed via desktop browsers. Leadership uses it to plan missions, manage teams, approve budgets, review performance and configure the system.
- **Mobile apps** (Android, iOS, Huawei) are used by field teams during missions — subscribing to missions, logging activities, submitting reports, and receiving push notifications.
- **Missions** are the core workflow: selecting a school, setting dates, assigning roles, tracking sessions, managing finances, completing the debrief, and following up with students through cohorts.
- **Financial flow** is structured: budget estimates → requisitions with multi-step approvals → fund allocation → payment recording (Paystack integration) → automated reporting.
- **Notifications** are delivered by push (FCM) and WhatsApp to mission group chats, keeping teams informed about approvals, updates and cancellations.
- **Data import** supports legacy SQL dumps from previous systems, merged into the tenant database with automatic RLS policy application.

## Capabilities and Constraints

### Confirmed Functionality

- **Mission management** — full lifecycle: create, approve, reject, cancel, complete. Includes subscriptions, offline members, sessions, transcripts, FAQs, questions, ground suggestions, debrief notes, weather forecasts and media uploads.
- **School directory** — schools with contacts, terms, class groups, and full mission history.
- **Member management** — profiles, groups, memberships, departments, gifts, course enrollments. Excel import/export. Member approval workflow.
- **Student follow-up (Cohorts)** — cohort → course → module → lesson hierarchy. Cohort-mission linking for post-mission discipleship tracking.
- **Events and announcements** — PRF events with speakers, subscriptions, participants. Organisation-wide announcements.
- **Prayer and spiritual life** — prayer prompts, prayer requests with responses, soul records and decisions for Christ tracking.
- **Finance** — accounting events, budget estimates, requisitions with approval workflow, allocation entries, payments, refunds, expense categories, transfer rates.
- **E-learning** — courses, modules, lessons with member enrollment and engagement tracking.
- **Admin panel** — 40 Filament resources, 31 dashboard widgets, role-based navigation, global search, database notifications.
- **Multi-tenancy** — domain-based tenant isolation via stancl/tenancy with RLS policies.
- **API** — versioned REST API (v1, v2) with Sanctum auth, Spatie QueryBuilder for filtering/sorting/includes.
- **Media** — Spatie Media Library integration for missions, events, members, and mission sessions. Azure Blob Storage support.
- **Integrations** — Paystack (payments), Firebase Cloud Messaging (push notifications), WhatsApp (group notifications), Google Maps.
- **Observability** — Laravel Pulse dashboards, Telescope debugging, Spatie Activity Log for audit trails.
- **Security** — Sanctum tokens, request-signature validation, webhook signature middleware, rate limiting, role-based permissions via Spatie.

### Technical Constraints

- PHP 8.4+, Laravel 12, Filament 5, Livewire 4
- PostgreSQL database with row-level security for multi-tenancy
- Domain-based tenant resolution (not path-based)
- Currency: Kenyan Shillings (KES)
- Phone numbers: Kenyan format default (+254)

### Decided Product Facts

- Two Filament panels: Central (tenant/user management) and Tenant (per-organisation admin)
- 7 navigation groups in the tenant panel mapping to ministry roles
- Mission approval workflow: draft → approved → fully subscribed → serviced → complete
- Requisition approval workflow with multi-step approval
- Member approval required before system access

## Brand Commitments

No external brand assets, style guides, or voice guidelines have been provided as binding constraints. The current Filament theme uses Amber as the primary color for the tenant panel and Indigo for the central panel. Future design work may evolve the visual identity freely.

## Evidence on Hand

- Product brief: `docs/product-brief.md`
- Feature set documentation: `docs/feature-set.md`
- Architecture guide: `AGENTS.md`
- System access documentation: `docs/system-access.md`
- Multitenancy implementation plan: `docs/multitenancy-implementation-plan.md`
- 88 Eloquent models in `app/Models/`
- 40 Filament resources in `app/Filament/Resources/`
- 31 dashboard widgets in `app/Filament/Widgets/`
- API routes in `routes/api/v1.php` and `routes/api/v2.php`
- Filament panel providers: `app/Providers/Filament/TenantPanelProvider.php` and `CentralPanelProvider.php`

## Product Principles

1. **Mission-first design** — every feature ultimately serves the planning, execution or follow-up of a mission. Features that do not connect to this workflow should justify their existence clearly.

2. **Financial accountability is non-negotiable** — every shilling spent must be traceable from budget estimate through requisition approval to payment recording. The system must make transparent accounting effortless, not optional.

3. **Role clarity over feature parity** — not every user needs access to everything. Navigation groups, permissions and approval workflows enforce that people see and do only what their role requires.

4. **Field-ready, not field-hostile** — the admin panel serves desktop users, but the data it manages is consumed by mobile apps in the field. Design decisions must account for both contexts.

5. **Multi-tenant by default** — every tenant's data is isolated. The platform must work identically whether serving one fellowship or twenty.

## Accessibility & Inclusion

No product-specific accessibility requirements have been established beyond Filament 5's built-in accessibility features. The admin panel is used primarily by desktop users in Kenya. Mobile apps serve field teams who may have limited connectivity.
