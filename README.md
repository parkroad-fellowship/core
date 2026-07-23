# PRF Core

PRF Core is the backend API for the PRF SuperApp ecosystem.

## Maintenance & Support

- This project is maintained & it's development sponsored by [FROG Technologies](https://frog.ke) in conjunction with Parkroad Fellowship.
- Fellowship website: [Parkroad Fellowship](https://parkroadfellowship.org).
- To get hosting & setup assistance, email `engineering@parkroadfellowship.org` or open an issue on GitHub.

## Community Links

- Fellowship Website: [https://parkroadfellowship.org](https://parkroadfellowship.org)
- Facebook: [https://www.facebook.com/ParkRdFellowship](https://www.facebook.com/ParkRdFellowship)
- Instagram: [https://www.instagram.com/parkroadfellowship](https://www.instagram.com/parkroadfellowship)
- TikTok: [https://www.tiktok.com/@parkroad_fellowship](https://www.tiktok.com/@parkroad_fellowship)
- YouTube: [https://www.youtube.com/@parkroadfellowship](https://www.youtube.com/@parkroadfellowship)

## Setup

### Option A: Local Development

#### Requirements
1. PHP 8.5
2. PostgreSQL
3. Node.js / Bun
4. A valid `.env` file

#### Procedure
1. Clone the repository and enter the project folder:
   - `git clone <repository-url>`
   - `cd prf`
2. Install dependencies:
   - `bun install`
   - `composer install`
3. Prepare the database:
   - `php artisan migrate:fresh --seed`
4. Run the development server:
   - `composer run dev`

### Option B: Docker (Recommended)

You can run this project using Docker Compose (recommended) or Dockerfile directly.

#### Requirements
1. Docker Desktop or OrbStack (macOS)
2. A valid environment file (`.env` or `manifests/docker/.env.docker`)

#### Procedure
1. Clone the repository and enter the project folder:
   - `git clone <repository-url>`
   - `cd prf`

2. Start the stack with Docker Compose:
   - `cd manifests/docker`
   - `docker compose up --build`

3. Available services:
   - API app: `http://localhost:8060` (container `8050`)
   - Reverb/WebSockets: `ws://localhost:9070` (container `9060`)
   - PostgreSQL: `localhost:5433`
   - Dragonfly: `localhost:6380`
   - Gotenberg: `http://localhost:7001`
   - Elasticsearch: `http://localhost:9200`
   - Kibana: `http://localhost:5601`

4. Run migrations and seed data:
   - `docker compose exec app php artisan migrate:fresh --seed`

5. Stop the stack:
   - `docker compose down`

#### Dockerfile-only Procedure
1. Build the image:
   - `docker build --pull --rm -f Dockerfile -t prf:latest .`
2. Run the image:
   - `docker run --env-file .env prf:latest`

## Importing a Legacy SQL Dump

Use this to import a pre-tenant PostgreSQL dump into a new single tenant.

### Prerequisites

- `pg_restore` must be available (from PostgreSQL client tools)
- Database must exist and be reachable via Laravel's DB config
- Dump must be a pg_restore custom-format file

### Usage

```bash
# Local dry run
php artisan tenants:import-legacy-sql \
  --file="Data Upload/prf-202607131227.sql" \
  --name="Parkroad Fellowship" \
  --slug=app \
  --force

# Production (during maintenance window)
php artisan tenants:import-legacy-sql \
  --file=/path/to/dump.sql \
  --name="Parkroad Fellowship" \
  --slug=app \
  --admin-email=admin@parkroadfellowship.org \
  --force
```

### What the command does

1. Restores the dump using `pg_restore --clean`
2. Runs prerequisite migrations (adds `tenant_id` columns, creates `tenant_user` pivot)
3. Creates the tenant via `CreateTenantAction`
4. Backfills `tenant_id` on all tenant-owned tables
5. Adds all imported users to `tenant_user` as members
6. Runs remaining migrations (NOT NULL constraints, FK constraints, etc.)
7. Revokes old personal access tokens
8. Validates data integrity

### Production checklist

1. Take a database snapshot/backup before running
2. Run in a maintenance window
3. Verify with `php artisan tenants:validate-data` after import
4. Test login and API access with an existing user account

## Open Source Contribution Standards

This repository welcomes contributions. Please review:

- [Contributing Guide](./CONTRIBUTING.md)
- [Code of Conduct](./CODE_OF_CONDUCT.md)
- [Security Policy](./SECURITY.md)
- [Support](./SUPPORT.md)
- [System Access Documentation](./docs/system-access.md)
- [Feature Set Documentation](./docs/feature-set.md)
- [Product Brief](./docs/product-brief.md)
- [Acknowledgements](./docs/acknowledgements.md)

## Public Repository Best Practices

- Keep pull requests focused and small.
- Run tests and formatting before opening a PR.
- Use issue templates for bug reports and feature proposals.
- Keep CI checks green before merge.
- Avoid committing secrets or production credentials.
- Document notable changes in release notes/changelog.

## License

This project is licensed under [Parkroad Fellowship Public Ministry License 1.0](./LICENSE).

### Allowed

- Use, adapt and deploy for noncommercial ministry or nonprofit use cases.
- Use by charitable, educational and public-interest organizations.
- Share improvements under the same noncommercial licensing constraints.

### Not allowed

- Selling this software or offering it as a paid commercial product.
- Commercial hosting or commercialization of the software.
- Any use that violates the terms in the [LICENSE](./LICENSE).

For setup and hosting assistance, contact `engineering@parkroadfellowship.org`.
