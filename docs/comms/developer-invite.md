# Open Source Announcement + Developer Invite

Greetings everyone,

Since our development journey began in 2024, the Parkroad Fellowship app ecosystem has grown into a robust suite of digital tools built to support missions, leadership operations, discipleship and accountability.

Today, we are happy to share this as one combined update:

1. We are open source.
2. We are inviting developers to try the platform to see if it would meet your needs and allow you to fork and customise.

By opening the codebase, we hope to provide engineering inspiration for fellow developers and make customisation possible for changing ministry and fellowship needs in the world.

## Open Source Repositories

---

## The Repositories

| App | Purpose | Repository |
|---|---|---|
| **PRF Core** | Backend API powering the entire ecosystem | [https://github.com/parkroad-fellowship/core](https://github.com/parkroad-fellowship/core) |
| **PRF Missions** | Mobile app for field team members | [https://github.com/parkroad-fellowship/missions](https://github.com/parkroad-fellowship/missions) |
| **PRF Leadership** | Mobile app for leaders and mission desk | [https://github.com/parkroad-fellowship/leadership](https://github.com/parkroad-fellowship/leadership) |
| **PRF Design** | Brand assets and design guidelines | [https://github.com/parkroad-fellowship/prf_design](https://github.com/parkroad-fellowship/prf_design) |

## Why Open Source

Feel free to fork the repositories, open issues and submit pull requests. Let us build better fellowship systems together.

## Developer Invite

The ecosystem now covers mission planning, member management, financial workflows, discipleship tracking, prayer, events and a full Filament-powered admin panel, all backed by a versioned REST API.

This is currently a developer-first release. There are no click-through demo builds. To experience the system, run the code locally or use staging/development app flavours against the demo environment.

## Technology Stack

- Mobile apps: Flutter (PRF Missions and PRF Leadership)
- Backend API: Laravel 12 (PHP 8.5)
- Admin panel: Filament (Livewire + Tailwind)
- Database: PostgreSQL
- Realtime and async: Queues + WebSockets (Reverb)
- Integrations: Paystack, Firebase Cloud Messaging
- Deployment options: Local native setup, Docker Compose stack or Kamal

---

## Running Locally

### PRF Core (Backend API)

Full setup instructions are in the [README](../../README.md).

### Mobile Apps (Missions and Leadership)

Clone the relevant app repository, set the API base URL in the staging/development flavour configuration to point to your local or our staging environment, and run a standard Flutter build.

If you just want to test the mobile experience without building from source, use the staging/development builds from Firebase App Distribution:

**PRF Leadership**

| Flavour | Link |
|---|---|
| Staging / Demo | https://appdistribution.firebase.dev/i/0d8820b0a1f93d92 |

**PRF Missions**

| Flavour | Link |
|---|---|
| Staging / Demo | https://appdistribution.firebase.dev/i/0d8820b0a1f93d92 |

You will need to request access via the Firebase App Distribution link — follow the on-screen prompt to register your device.

## Environment Disclaimer

Some features may not fully work on staging and development because a few required secrets are not yet configured in those environments to prevent abuse.

Known limitations can include:

- Google Maps on the admin console
- Sending SMS
- Sending emails
- Firebase notifications (we are actively improving this, especially where environments are isolated)

---

## Demo Environment

Once you have the backend running, you can point your client at our hosted demo environment or run everything locally.

- **Demo URL:** https://demo.parkroadfellowship.org
- **Email:** `admin@example.org`
- **Password:** `asZDcVt7Q`

PRF Leadership
- **Email:** `chairperson@example.org`
- **Password:** `asZDcVt7Q`

PRF Missions
- **Email:** `member.hirthe@example.org`
- **Password:** `asZDcVt7Q`

> This credential is for non-production environments only. Email/Password auth is disabled in production.

---

## What to Explore First

- The Filament admin panel — covers all resource domains with a well-structured interface
- The mission workflow API — full lifecycle from creation through approval to completion, with automated notifications
- The financial flows — requisitions with multi-step approvals, allocation entries, Paystack webhook handling
- The v1 and v2 API routes — versioned with Sanctum auth, Spatie QueryBuilder filtering and eager loading

---

## How to Contribute

Fork any repo, open issues, or submit pull requests. Contributions are welcomed under the terms of the [Parkroad Fellowship Public Ministry Licence](../../LICENSE).

If you are building something on top of the PRF stack, we would love to hear about it. Drop us a message at engineering@parkroadfellowship.org.

---

The PRF ecosystem is made possible by generous sponsorships and free tiers from a number of organisations. See our [Acknowledgements](../acknowledgements.md) for the full list.

Best regards,
**Parkroad Fellowship Engineering**
