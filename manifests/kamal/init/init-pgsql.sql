-- init-pgsql.sql
-- Runs once on first Postgres container start (docker-entrypoint-initdb.d)

-- Create application databases
CREATE DATABASE prf;
CREATE DATABASE prf_stg;
CREATE DATABASE prf_dev;

-- Create the RLS user used by Stancl Tenancy for Row-Level Security.
-- The password is a placeholder; tenants:rls sets the real one via env.
DO $$
BEGIN
    CREATE ROLE tenancy_rls_user WITH LOGIN PASSWORD 'changeme';
EXCEPTION WHEN duplicate_object THEN
    ALTER ROLE tenancy_rls_user PASSWORD 'changeme';
END
$$;

-- Grant CONNECT on each database (required before any per-database grants)
GRANT CONNECT ON DATABASE prf TO tenancy_rls_user;
GRANT CONNECT ON DATABASE prf_stg TO tenancy_rls_user;
GRANT CONNECT ON DATABASE prf_dev TO tenancy_rls_user;