# PRF Core

## Setup

### A: Normal Option
#### Requirements
1. PHP 8.5
2. PostgreSQL
3. Node.js 
4. Pre-provided `.env` file

#### Procedure
1. Clone the repository & `cd prf`
2. Install Node.js dependencies: `bun install`
3. Install PHP depedendencies: `composer install`
4. Run migrations and setup sample data: `php artisan migrate:fresh --seed`


### B: Docker Option
This project uses one Dockerfile, so the Database has to be hosted on the local machine. En

#### Requirements
1. Docker or OrbStack (MacOs)
2. PostgreSQL
3. Pre-provided `.env` file

#### Procedure
1. Clone the repository & `cd prf`
2. Build the image: `docker build --pull --rm -f "Dockerfile" -t prf:latest "."`
3. Run the project: `docker run --env-file .env prf`. In the `.env` file, ensure to have `# DB_HOST=docker.for.mac.localhost`
