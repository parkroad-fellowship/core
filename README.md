# PRF Core

PRF Core is the backend API for the PRF SuperApp ecosystem.

## Maintenance & Support

- This project is maintained & sponsored by [FROG Technologies](https://frog.ke).
- Fellowship website: [Park Road Fellowship](https://parkroadfellowship.org).
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

## Open Source Contribution Standards

This repository welcomes contributions. Please review:

- [Contributing Guide](./CONTRIBUTING.md)
- [Code of Conduct](./CODE_OF_CONDUCT.md)
- [Security Policy](./SECURITY.md)
- [Support](./SUPPORT.md)
- [System Access Documentation](./docs/system-access.md)

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

- Use, adapt, and deploy for noncommercial ministry or nonprofit use cases.
- Use by charitable, educational and public-interest organizations.
- Share improvements under the same noncommercial licensing constraints.

### Not allowed

- Selling this software or offering it as a paid commercial product.
- Commercial hosting or commercialization of the software.
- Any use that violates the terms in the [LICENSE](./LICENSE).

For setup and hosting assistance, contact `engineering@parkroadfellowship.org`.
