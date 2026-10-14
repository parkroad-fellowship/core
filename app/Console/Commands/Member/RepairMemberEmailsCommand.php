<?php

namespace App\Console\Commands\Member;

use App\Console\Concerns\RunsForEachTenant;
use App\Enums\PRFMemberEmailMode;
use App\Enums\PRFWorkspaceStatus;
use App\Helpers\Utils;
use App\Models\AppSetting;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fixes tenants whose "organisation" email domain was never a real Workspace domain (blank,
 * public webmail such as gmail.com, or the app's hosting domain): switches them to personal
 * email sign-in and moves each member's login to their personal email where that is safe.
 */
class RepairMemberEmailsCommand extends Command
{
    use RunsForEachTenant;

    protected $signature = 'prf:members:repair-emails
        {--tenant= : Only repair this tenant (id)}
        {--dry-run : Report what would change without saving}';

    protected $description = 'Switch tenants with fabricated member emails to personal-email sign in';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $rows = [];

        $this->forEachTenant(function (Tenant $tenant) use ($dryRun, &$rows): void {
            if (filled($this->option('tenant')) && $tenant->id !== $this->option('tenant')) {
                return;
            }

            $domain = strtolower(trim((string) AppSetting::get('organization.org_email_domain', '')));

            if (!$this->domainIsFabricated($tenant, $domain)) {
                $rows[] = [$tenant->name, '-', 'OK', "Uses {$domain}"];

                return;
            }

            DB::transaction(function () use ($tenant, $domain, $dryRun, &$rows): void {
                if (!$dryRun) {
                    AppSetting::set(
                        'organization.member_email_mode',
                        PRFMemberEmailMode::PERSONAL->value,
                        'organization',
                        'integer',
                    );
                    AppSetting::set('organization.org_email_domain', '', 'organization');
                }

                $rows[] = [
                    $tenant->name,
                    '-',
                    $dryRun ? 'WOULD SWITCH' : 'SWITCHED',
                    "'{$domain}' is not a Workspace domain",
                ];

                Member::query()
                    ->with('user')
                    ->lazyById()
                    ->each(function (Member $member) use ($tenant, $dryRun, &$rows): void {
                        $rows[] = [$tenant->name, $member->full_name, ...$this->repairMember($member, $dryRun)];
                    });
            });
        }, activeOnly: false);

        $this->table(['Tenant', 'Member', 'Result', 'Detail'], $rows);

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function repairMember(Member $member, bool $dryRun): array
    {
        $target = strtolower(trim((string) $member->personal_email));
        $user = $member->user;

        if ($target === '') {
            return ['CONFLICT', 'No personal email on record'];
        }

        if ($user === null) {
            return ['CONFLICT', 'Member has no login user'];
        }

        if ($user->email === $target && $member->email === $target) {
            return ['OK', $target];
        }

        $owner = User::withTrashed()->where('email', $target)->whereKeyNot($user->getKey())->first();

        if ($owner !== null) {
            return ['CONFLICT', "{$target} already belongs to another account (user #{$owner->id})"];
        }

        if (!$dryRun) {
            $user->update(['email' => $target]);
            $member->update(['email' => $target, 'workspace_status' => PRFWorkspaceStatus::NOT_APPLICABLE]);
        }

        return [$dryRun ? 'WOULD MOVE' : 'MOVED', "{$user->email} → {$target}"];
    }

    private function domainIsFabricated(Tenant $tenant, string $domain): bool
    {
        return (
            $domain === ''
            || Utils::isPublicEmailDomain($domain)
            || $tenant->domains()->where('domain', $domain)->exists()
        );
    }
}
