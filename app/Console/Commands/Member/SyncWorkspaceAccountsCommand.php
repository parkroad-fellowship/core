<?php

namespace App\Console\Commands\Member;

use App\Console\Concerns\RunsForEachTenant;
use App\Contracts\Services\WorkspaceDirectoryInterface;
use App\Enums\PRFIntegration;
use App\Enums\PRFMemberEmailMode;
use App\Enums\PRFWorkspaceStatus;
use App\Helpers\Utils;
use App\Jobs\Member\ProvisionWorkspaceAccountJob;
use App\Models\Member;
use App\Models\Tenant;
use App\Services\Tenancy\TenantIntegrations;
use Illuminate\Console\Command;

/**
 * Backfill for organisation-domain tenants: links members to mailboxes that already exist in
 * Google Workspace and queues creation for the ones that do not.
 */
class SyncWorkspaceAccountsCommand extends Command
{
    use RunsForEachTenant;

    protected $signature = 'prf:members:sync-workspace
        {--tenant= : Only sync this tenant (id)}
        {--dry-run : Report what would change without saving}';

    protected $description = 'Link or create Google Workspace accounts for organisation-domain members';

    public function handle(WorkspaceDirectoryInterface $directory, TenantIntegrations $integrations): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $rows = [];

        $this->forEachTenant(function (Tenant $tenant) use ($directory, $integrations, $dryRun, &$rows): void {
            if (filled($this->option('tenant')) && $tenant->id !== $this->option('tenant')) {
                return;
            }

            if (Utils::memberEmailMode() !== PRFMemberEmailMode::ORGANISATION_DOMAIN) {
                return;
            }

            if (!$integrations->isConfigured(PRFIntegration::GOOGLE_WORKSPACE)) {
                $rows[] = [$tenant->name, '-', 'SKIPPED', 'Google Workspace is not configured'];

                return;
            }

            Member::query()
                ->whereNotNull('email')
                ->where('workspace_status', '!=', PRFWorkspaceStatus::PROVISIONED->value)
                ->lazyById()
                ->each(function (Member $member) use ($tenant, $directory, $dryRun, &$rows): void {
                    $existing = $directory->find((string) $member->email);

                    if ($existing !== null) {
                        if (!$dryRun) {
                            $member->update([
                                'workspace_user_id' => $existing->id,
                                'workspace_status' => $existing->suspended
                                    ? PRFWorkspaceStatus::SUSPENDED
                                    : PRFWorkspaceStatus::PROVISIONED,
                                'workspace_error' => null,
                                'workspace_provisioned_at' => $member->workspace_provisioned_at ?? now(),
                            ]);
                        }

                        $rows[] = [
                            $tenant->name,
                            (string) $member->email,
                            $dryRun ? 'WOULD LINK' : 'LINKED',
                            'Mailbox already exists',
                        ];

                        return;
                    }

                    if (!$dryRun) {
                        $member->update(['workspace_status' => PRFWorkspaceStatus::PENDING, 'workspace_error' => null]);
                        ProvisionWorkspaceAccountJob::dispatch($member);
                    }

                    $rows[] = [
                        $tenant->name,
                        (string) $member->email,
                        $dryRun ? 'WOULD CREATE' : 'QUEUED',
                        'No mailbox yet',
                    ];
                });

            // Recovery contacts for every live mailbox, so members can reset their own password.
            Member::query()
                ->whereNotNull('email')
                ->where('workspace_status', PRFWorkspaceStatus::PROVISIONED->value)
                ->lazyById()
                ->each(function (Member $member) use ($tenant, $directory, $dryRun, &$rows): void {
                    $phone = Utils::toE164($member->phone_number);

                    if ($phone === null) {
                        $rows[] = [
                            $tenant->name,
                            (string) $member->email,
                            'MISSING PHONE',
                            'Add a phone number to set the recovery phone',
                        ];
                    }

                    if (!$dryRun) {
                        $directory->updateRecovery((string) $member->email, $member->personal_email, $phone);
                    }
                });
        });

        $this->table(['Tenant', 'Email', 'Result', 'Detail'], $rows);

        return self::SUCCESS;
    }
}
