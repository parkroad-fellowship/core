<?php

namespace App\Console\Commands\Tenant;

use App\Jobs\Tenant\ProvisionTenantJob;
use App\Models\Tenant;
use Illuminate\Console\Command;

class CreateTenant extends Command
{
    protected $signature = 'tenants:create
        {name : Fellowship name}
        {slug? : Subdomain slug (auto-generated if omitted)}
        {--domain= : Custom domain (e.g., admin.fellowship.org)}
        {--admin-email= : Admin user email (must already exist or be created first)}
        {--confirm-promote-existing-admin : Required to promote an existing global user to tenant super admin}';

    protected $description = 'Create a new tenant and provision it';

    public function handle(): int
    {
        $tenant = Tenant::create([
            'name' => $this->argument('name'),
            'slug' => $this->argument('slug'),
            'is_active' => true,
        ]);

        if ($domain = $this->option('domain')) {
            $tenant->addDomain($domain);
        }

        ProvisionTenantJob::dispatchSync(
            $tenant,
            $this->option('admin-email'),
            (bool) $this->option('confirm-promote-existing-admin'),
        );

        $this->info("Tenant created: {$tenant->id}");
        $this->info("Name: {$tenant->name}");
        $this->info("Slug: {$tenant->slug}");

        return self::SUCCESS;
    }
}
