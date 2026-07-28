<?php

namespace App\Jobs\Tenant;

use App\Actions\Tenant\AddTenantMemberAction;
use App\Helpers\Utils;
use App\Models\AppSetting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public ?string $adminEmail = null,
        public string $adminPassword = '',
        public bool $confirmPromoteExistingAdmin = false,
    ) {}

    public function handle(): void
    {
        tenancy()->initialize($this->tenant);

        try {
            (new \Database\Seeders\RolesAndPermissionsSeeder)->run();
            (new \Database\Seeders\AppSettingSeeder)->run();

            $orgDomain = $this->tenant->domains->first()?->domain;

            AppSetting::updateOrCreate(
                ['tenant_id' => tenant('id'), 'key' => 'organization.org_email_domain'],
                ['tenant_id' => tenant('id'), 'group' => 'organization', 'type' => 'string', 'value' => $orgDomain],
            );
            (new \Database\Seeders\GroupSeeder)->run();

            if ($this->adminEmail) {
                $user = User::query()->where('email', $this->adminEmail)->first();

                if ($user === null) {
                    $password = $this->adminPassword ?: Utils::randomPassword();

                    $user = User::create([
                        'email' => $this->adminEmail,
                        'name' => $this->tenant->name.' Admin',
                        'password' => $password,
                    ]);

                    Log::info('Tenant admin user created', [
                        'tenant' => $this->tenant->slug,
                        'admin_email' => $this->adminEmail,
                        'admin_password' => $password,
                    ]);
                } elseif (! $this->confirmPromoteExistingAdmin) {
                    throw new \RuntimeException('Refusing to promote existing global user without --confirm-promote-existing-admin.');
                }

                $user->assignRole('super admin');

                app(AddTenantMemberAction::class)
                    ->handle($this->tenant, $user, 'super admin');

                $user->notify(new \App\Notifications\Tenant\WelcomeNotification($this->tenant));
            }
        } catch (\Throwable $e) {
            Log::error('Tenant provisioning failed', ['tenant' => $this->tenant->id]);
            throw $e;
        } finally {
            tenancy()->end();
        }
    }
}
