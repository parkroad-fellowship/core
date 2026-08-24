#!/bin/bash
# Creates the stancl/tenancy RLS role used by PostgresRLSBootstrapper.
# Credentials are interpolated by docker compose from the host .env.
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" <<-EOSQL
    DO \$\$
    BEGIN
        IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = '${TENANCY_RLS_USERNAME}') THEN
            CREATE ROLE ${TENANCY_RLS_USERNAME} WITH NOINHERIT LOGIN PASSWORD '${TENANCY_RLS_PASSWORD}';
        END IF;
    END
    \$\$;

    GRANT USAGE ON SCHEMA public TO ${TENANCY_RLS_USERNAME};
    GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO ${TENANCY_RLS_USERNAME};
    GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO ${TENANCY_RLS_USERNAME};

    -- Tests run everything as this single identity (migrations included),
    -- so it owns the database and schema. RLS policies still apply to it.
    ALTER DATABASE testing OWNER TO ${TENANCY_RLS_USERNAME};
    GRANT CREATE, USAGE ON SCHEMA public TO ${TENANCY_RLS_USERNAME};
EOSQL
