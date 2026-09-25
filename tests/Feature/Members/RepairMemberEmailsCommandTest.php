<?php

use App\Enums\PRFMemberEmailMode;
use App\Helpers\Utils;
use App\Models\AppSetting;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\GroupSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, GroupSeeder::class]);
    usePersonalEmail();

    // Simulate the old behaviour: fabricated @gmail.com logins.
    $this->member = Member::factory()->create(['personal_email' => 'jane.real@yahoo.com']);
    $this->member->user->update(['email' => 'jane.doe@gmail.com']);
    $this->member->updateQuietly(['email' => 'jane.doe@gmail.com']);
    AppSetting::set(
        'organization.member_email_mode',
        PRFMemberEmailMode::ORGANISATION_DOMAIN->value,
        'organization',
        'integer',
    );
    AppSetting::set('organization.org_email_domain', 'gmail.com', 'organization');
});

it('reports what it would change on a dry run without saving', function () {
    $this->artisan('prf:members:repair-emails', ['--dry-run' => true])->assertSuccessful();

    initTenancy(\App\Models\Tenant::query()->findOrFail($this->member->tenant_id));

    expect($this->member->user->fresh()->email)->toBe('jane.doe@gmail.com');
});

it('moves members to their personal email and switches the tenant to personal mode', function () {
    $this->artisan('prf:members:repair-emails')->assertSuccessful();

    initTenancy(\App\Models\Tenant::query()->findOrFail($this->member->tenant_id));

    expect($this->member->user->fresh()->email)
        ->toBe('jane.real@yahoo.com')
        ->and(Utils::memberEmailMode())
        ->toBe(PRFMemberEmailMode::PERSONAL);
});

it('leaves members whose personal email belongs to someone else for manual review', function () {
    User::factory()->create(['email' => 'jane.real@yahoo.com']);

    $this->artisan('prf:members:repair-emails')->expectsOutputToContain('CONFLICT')->assertSuccessful();

    expect($this->member->user->fresh()->email)->toBe('jane.doe@gmail.com');
});
