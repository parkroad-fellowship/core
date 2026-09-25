<?php

namespace App\Jobs\Tenant;

use App\Actions\Tenant\AddTenantMemberAction;
use App\Enums\PRFMemberEmailMode;
use App\Enums\PRFRole;
use App\Helpers\Utils;
use App\Models\AppSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Tenant\TenantProvisionedNotification;
use Database\Seeders\AppSettingSeeder;
use Database\Seeders\GroupSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TenantReferenceDataSeeder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use RuntimeException;
use Throwable;

/**
 * Seeds a new tenant (roles, settings, groups), records how its members sign in and
 * creates or promotes its first admin. Always run synchronously.
 */
class ProvisionTenantJob
{
    use Dispatchable;

    public function __construct(
        public Tenant $tenant,
        public ?string $adminEmail = null,
        public string $adminPassword = '',
        public bool $confirmPromoteExistingAdmin = false,
        public PRFMemberEmailMode $memberEmailMode = PRFMemberEmailMode::PERSONAL,
        public ?string $orgEmailDomain = null,
    ) {}

    public function handle(): void
    {
        if ($this->memberEmailMode === PRFMemberEmailMode::ORGANISATION_DOMAIN) {
            if (blank($this->orgEmailDomain) || Utils::isPublicEmailDomain((string) $this->orgEmailDomain)) {
                throw new RuntimeException('Organisation-domain tenants need their own (non-webmail) email domain.');
            }
        }

        tenancy()->initialize($this->tenant);

        try {
            new RolesAndPermissionsSeeder()->run();
            new AppSettingSeeder()->run();
            new GroupSeeder()->run();
            new TenantReferenceDataSeeder()->run();

            AppSetting::set('organization.member_email_mode', $this->memberEmailMode->value, 'organization', 'integer');
            AppSetting::set(
                'organization.org_email_domain',
                $this->memberEmailMode === PRFMemberEmailMode::ORGANISATION_DOMAIN
                    ? strtolower((string) $this->orgEmailDomain)
                    : '',
                'organization',
            );

            if ($this->adminEmail) {
                $this->provisionAdmin();
            }
        } catch (Throwable $exception) {
            Log::error('Tenant provisioning failed', [
                'tenant' => $this->tenant->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            tenancy()->end();
        }
    }

    private function provisionAdmin(): void
    {
        $email = strtolower((string) $this->adminEmail);
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = User::create([
                'email' => $email,
                'name' => $this->tenant->name . ' Admin',
                'password' => Hash::make($this->adminPassword !== '' ? $this->adminPassword : Utils::defaultPassword()),
            ]);
        } elseif (!$this->confirmPromoteExistingAdmin) {
            throw new RuntimeException(
                'Refusing to promote existing global user without --confirm-promote-existing-admin.',
            );
        }

        $user->assignRole(PRFRole::SUPER_ADMIN);

        app(AddTenantMemberAction::class)->handle($this->tenant, $user, PRFRole::SUPER_ADMIN->value);

        // A reset link instead of a password: nothing secret is logged or emailed.
        $user->notify(
            new TenantProvisionedNotification($this->tenant, $this->memberEmailMode, Password::createToken($user)),
        );
    }
}
