<?php

namespace App\Tenancy;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\MigrationEnded;
use Illuminate\Database\Events\MigrationStarted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Lets migrations change tenant tables freely once Row Level Security is in place.
 *
 * Postgres refuses to alter a column that an RLS policy uses (e.g. tenant_id), and forced RLS
 * hides rows from the migrating user. Before each migration this lifts every policy (and the
 * FORCE flag); afterwards it puts them back exactly as they were. Laravel runs each Postgres
 * migration in a transaction and fires these events inside it, so other connections never see
 * a table without its policy.
 *
 * Policies for brand-new tenant tables are created by `tenants:sync-rls`, which runs right
 * after `migrate` on every deploy (.fly/scripts/migrate.sh).
 */
class RLSMigrationGuard
{
    /**
     * @var list<object{policyname: string, tablename: string, permissive: string, roles: string, cmd: string, qual: ?string, with_check: ?string}>
     */
    private array $policies = [];

    /**
     * @var list<string>
     */
    private array $forcedTables = [];

    public function suspend(MigrationStarted $event): void
    {
        $connection = $this->connection();

        if ($connection === null) {
            return;
        }

        $this->policies = $connection->select('SELECT policyname, tablename, permissive, roles::text AS roles, cmd, qual, with_check
               FROM pg_policies WHERE schemaname = current_schema()');

        $this->forcedTables = array_map(fn(object $row): string => (string) $row->relname, $connection->select(
            "SELECT c.relname FROM pg_class c JOIN pg_namespace n ON n.oid = c.relnamespace
                  WHERE n.nspname = current_schema() AND c.relkind = 'r' AND c.relforcerowsecurity",
        ));

        foreach ($this->policies as $policy) {
            $connection->statement(sprintf(
                'DROP POLICY IF EXISTS %s ON %s',
                $this->quote($policy->policyname),
                $this->quote($policy->tablename),
            ));
        }

        foreach ($this->forcedTables as $table) {
            $connection->statement(sprintf('ALTER TABLE %s NO FORCE ROW LEVEL SECURITY', $this->quote($table)));
        }
    }

    public function restore(MigrationEnded $event): void
    {
        $connection = $this->connection();

        if ($connection === null) {
            return;
        }

        foreach ($this->forcedTables as $table) {
            $this->attempt($connection, sprintf('ALTER TABLE %s FORCE ROW LEVEL SECURITY', $this->quote($table)));
        }

        foreach ($this->policies as $policy) {
            $this->attempt($connection, $this->createPolicySQL($policy));
        }

        $this->policies = [];
        $this->forcedTables = [];
    }

    /**
     * A migration that dropped a table or column a policy used makes that policy invalid; it's
     * skipped here (in a savepoint, so the migration's transaction survives) and rebuilt by
     * tenants:sync-rls.
     */
    private function attempt(Connection $connection, string $sql): void
    {
        try {
            $connection->transaction(fn() => $connection->statement($sql));
        } catch (Throwable $exception) {
            Log::warning('RLS policy not restored after migration; tenants:sync-rls will rebuild it', [
                'sql' => $sql,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  object{policyname: string, tablename: string, permissive: string, roles: string, cmd: string, qual: ?string, with_check: ?string}  $policy
     */
    private function createPolicySQL(object $policy): string
    {
        $roles = collect(explode(',', trim($policy->roles, '{}')))
            ->map(fn(string $role): string => trim($role, '"'))
            ->filter()
            ->map(fn(string $role): string => $role === 'public' ? 'PUBLIC' : $this->quote($role))
            ->implode(', ');

        return sprintf(
            'CREATE POLICY %s ON %s AS %s FOR %s TO %s%s%s',
            $this->quote($policy->policyname),
            $this->quote($policy->tablename),
            $policy->permissive,
            $policy->cmd,
            $roles !== '' ? $roles : 'PUBLIC',
            $policy->qual !== null ? " USING ({$policy->qual})" : '',
            $policy->with_check !== null ? " WITH CHECK ({$policy->with_check})" : '',
        );
    }

    private function connection(): ?Connection
    {
        $connection = DB::connection();

        return $connection->getDriverName() === 'pgsql' ? $connection : null;
    }

    private function quote(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
