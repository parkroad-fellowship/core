<?php

use App\Enums\PRFWorkspaceStatus;
use App\Jobs\Member\ProvisionWorkspaceAccountJob;
use App\Models\Member;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Member\MemberCredentialsIssuedNotification;
use Database\Seeders\GroupSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, GroupSeeder::class]);
});

describe('personal email mode', function () {
    it('signs members in with their personal email', function () {
        usePersonalEmail();

        $member = Member::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'personal_email' => 'Jane.Doe@Gmail.com',
        ]);

        expect($member->fresh())
            ->email->toBe('jane.doe@gmail.com')
            ->workspace_status->toBe(PRFWorkspaceStatus::NOT_APPLICABLE)->and($member->user->email)->toBe(
                'jane.doe@gmail.com',
            )->and($member->routeNotificationForMail())->toBe('Jane.Doe@Gmail.com');
    });

    it('shares one login when the same person belongs to two organisations', function () {
        usePersonalEmail();
        $first = Member::factory()->create(['personal_email' => 'shared@gmail.com']);

        $otherTenant = Tenant::factory()->create();
        initTenancy($otherTenant);
        $this->seed([RolesAndPermissionsSeeder::class, GroupSeeder::class]);
        usePersonalEmail();
        $second = Member::factory()->create(['personal_email' => 'shared@gmail.com']);

        expect($second->user_id)
            ->toBe($first->user_id)
            ->and(User::query()->where('email', 'shared@gmail.com')->count())
            ->toBe(1)
            ->and($second->user->tenants()->count())
            ->toBe(2);
    });

    it('keeps the login for other organisations when a member is removed from one', function () {
        usePersonalEmail();
        $tenantA = tenant();
        $memberA = Member::factory()->create(['personal_email' => 'shared@gmail.com']);

        initTenancy(Tenant::factory()->create());
        $this->seed([RolesAndPermissionsSeeder::class, GroupSeeder::class]);
        usePersonalEmail();
        Member::factory()->create(['personal_email' => 'shared@gmail.com']);

        initTenancy($tenantA);
        $memberA->delete();

        $user = User::query()->where('email', 'shared@gmail.com')->sole();

        expect($user->trashed())->toBeFalse()->and($user->belongsToTenant($tenantA->id))->toBeFalse();
    });
});

describe('organisation domain mode', function () {
    it('never generates addresses on public webmail domains', function () {
        useOrganisationDomain('gmail.com', withWorkspace: false);

        $member = Member::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'personal_email' => 'john@yahoo.com',
        ]);

        expect($member->fresh()->email)->toBe('john@yahoo.com');
    });

    it('allocates first.last on the organisation domain and creates the Workspace mailbox', function () {
        Notification::fake();
        $workspace = useOrganisationDomain();

        $member = Member::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'personal_email' => 'john@yahoo.com',
        ]);

        expect($member->fresh())
            ->email->toBe('john.doe@fellowship.org')
            ->workspace_status->toBe(PRFWorkspaceStatus::PROVISIONED)->and($workspace->created)->toHaveCount(1);

        Notification::assertSentOnDemand(
            MemberCredentialsIssuedNotification::class,
            fn($notification, array $channels, object $notifiable) => $notifiable->routes['mail'] === 'john@yahoo.com',
        );
    });

    it('skips addresses already taken locally or in Workspace', function () {
        Notification::fake();
        $workspace = useOrganisationDomain()->withExistingUser('john.doe2@fellowship.org');
        User::factory()->create(['email' => 'john.doe@fellowship.org']);

        $member = Member::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        expect($member->fresh()->email)->toBe('john.doe3@fellowship.org');
    });

    it('moves to the next address when Workspace reports a conflict', function () {
        Notification::fake();
        $workspace = useOrganisationDomain();
        $workspace->raceConflicts = ['john.doe@fellowship.org'];

        $member = Member::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        expect($member->fresh())
            ->email->toBe('john.doe2@fellowship.org')
            ->workspace_status->toBe(PRFWorkspaceStatus::PROVISIONED)->and($member->user->fresh()->email)->toBe(
                'john.doe2@fellowship.org',
            );
    });

    it('marks the member as failed when Workspace is not configured', function () {
        useOrganisationDomain(withWorkspace: false);

        $member = Member::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        expect($member->fresh()->workspace_status)->toBe(PRFWorkspaceStatus::FAILED);
    });

    it('never queues the temporary password', function () {
        expect(new ReflectionClass(MemberCredentialsIssuedNotification::class)->implementsInterface(ShouldQueue::class))
            ->toBeFalse()
            ->and(new ReflectionClass(ProvisionWorkspaceAccountJob::class)->hasProperty('password'))
            ->toBeFalse();
    });

    it('suspends the mailbox when the member is removed and restores it on restore', function () {
        Notification::fake();
        $workspace = useOrganisationDomain();
        $member = Member::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);

        $member->delete();

        expect($workspace->find('john.doe@fellowship.org')->suspended)->toBeTrue();

        $member->restore();

        expect($workspace->find('john.doe@fellowship.org')->suspended)->toBeFalse();
    });
});

describe('panel access', function () {
    it('lets leadership roles in and keeps plain members out', function () {
        $panel = filament()->getPanel('admin');

        $member = User::factory()->create();
        $member->assignRole('member');
        $chairperson = User::factory()->create();
        $chairperson->assignRole('chairperson');

        foreach ([$member, $chairperson] as $user) {
            app(\App\Actions\Tenant\AddTenantMemberAction::class)->handle(tenant(), $user, 'member');
        }

        expect($member->canAccessPanel($panel))->toBeFalse()->and($chairperson->canAccessPanel($panel))->toBeTrue();
    });
});
