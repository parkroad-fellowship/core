<?php

namespace App\Console\Commands\Tenant;

use App\Actions\Tenant\ReconcileMemberLinksAction;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class RepairMemberUserLinksCommand extends Command
{
    protected $signature = 'tenants:repair-member-links
        {tenant? : Tenant ID. If omitted, all tenants are repaired.}
        {--dry-run : Preview the repairs without applying them.}';

    protected $description = 'Re-link members and students to the user with the matching email within each tenant';

    public function handle(ReconcileMemberLinksAction $reconcile): int
    {
        $tenants = $this->resolveTenants();

        if ($tenants === null) {
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $hasIssues = false;

        foreach ($tenants as $tenant) {
            $prefix = $dryRun ? '[dry-run] ' : '';
            $this->info("{$prefix}Tenant [{$tenant->id}] ({$tenant->name})");

            $results = $reconcile->handle($tenant, $dryRun);

            foreach (['members' => 'member', 'students' => 'student'] as $key => $label) {
                $result = $results[$key];
                $this->line("  {$label}s: {$result['repaired']} re-linked, {$result['already_correct']} already correct");

                foreach ($result['unresolved'] as $row) {
                    $emailsStr = implode(', ', $row['emails']);
                    $this->error("    Unresolved {$label} [{$row['id']}] - no user found with email [{$emailsStr}]");
                    $hasIssues = true;
                }
            }
        }

        return $hasIssues ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return Collection<int, Tenant>|null
     */
    private function resolveTenants(): ?Collection
    {
        $tenantArg = $this->argument('tenant');

        if (is_string($tenantArg) && $tenantArg !== '') {
            /** @var Tenant|null $tenant */
            $tenant = Tenant::find($tenantArg);

            if (! $tenant) {
                $this->error("Tenant [{$tenantArg}] not found.");

                return null;
            }

            return collect([$tenant]);
        }

        return Tenant::query()->get();
    }
}
