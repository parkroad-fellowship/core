<?php

namespace App\Console\Commands\Tenant;

use App\Actions\Tenant\AddTenantMemberAction;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;

class AddMemberCommand extends Command
{
    protected $signature = 'tenants:add-member {tenant : Tenant slug} {email : User email} {--role=member}';

    protected $description = 'Add a user as a member of a tenant';

    public function handle(): int
    {
        $tenant = Tenant::where('data->slug', $this->argument('tenant'))->firstOrFail();
        $user = User::where('email', $this->argument('email'))->firstOrFail();

        app(AddTenantMemberAction::class)->handle(
            $tenant,
            $user,
            $this->option('role'),
        );

        $this->info("User '{$user->email}' added to tenant '{$tenant->name}' as {$this->option('role')}.");

        return self::SUCCESS;
    }
}
