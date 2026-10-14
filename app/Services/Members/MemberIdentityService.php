<?php

namespace App\Services\Members;

use App\Contracts\Services\WorkspaceDirectoryInterface;
use App\Enums\PRFIntegration;
use App\Enums\PRFMemberEmailMode;
use App\Enums\PRFWorkspaceStatus;
use App\Helpers\Utils;
use App\Models\Member;
use App\Models\User;
use App\Services\Tenancy\TenantIntegrations;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The single place that decides which address a member signs in with and creates their User.
 *
 * - Organisation-domain tenants: allocate first.last@domain, checking local users, members in
 *   every tenant and the tenant's Google Workspace, so the mailbox can be created without clashes.
 * - Personal-email tenants: the member's personal email is their identity; one User is shared
 *   when the same person belongs to several tenants.
 */
class MemberIdentityService
{
    private const MAX_CANDIDATES = 500;

    public function __construct(
        private readonly WorkspaceDirectoryInterface $directory,
        private readonly TenantIntegrations $integrations,
    ) {}

    public function mode(): PRFMemberEmailMode
    {
        return Utils::memberEmailMode();
    }

    /**
     * Create (or link) the member's login User and record it on the member.
     */
    public function provisionUser(Member $member): User
    {
        if ($member->user_id !== null && ($existing = $member->user) !== null) {
            return $existing;
        }

        return $this->mode() === PRFMemberEmailMode::ORGANISATION_DOMAIN
            ? $this->provisionOrganisationUser($member)
            : $this->provisionPersonalUser($member);
    }

    /**
     * Move a not-yet-provisioned member to the next free organisation address, e.g. after
     * Google Workspace reported that the current one is taken.
     */
    public function reallocateOrganisationEmail(Member $member): string
    {
        $user = $member->user ?? throw new RuntimeException("Member {$member->ulid} has no user.");

        foreach ($this->candidates($member, $this->organisationDomain()) as $email) {
            if ($email === $member->email || !$this->isAvailable($email)) {
                continue;
            }

            try {
                User::query()
                    ->getConnection()
                    ->transaction(fn() => $user->update(['email' => $email]));
            } catch (UniqueConstraintViolationException) {
                continue;
            }

            $member->update(['email' => $email]);

            return $email;
        }

        throw new RuntimeException("No free organisation address left for member {$member->ulid}.");
    }

    public function isAvailable(string $email): bool
    {
        $email = strtolower($email);

        if (User::withTrashed()->where('email', $email)->exists()) {
            return false;
        }

        if (Member::withoutGlobalScopes()->withTrashed()->where('email', $email)->exists()) {
            return false;
        }

        return (
            !$this->integrations->isConfigured(PRFIntegration::GOOGLE_WORKSPACE)
            || $this->directory->find($email) === null
        );
    }

    /**
     * first.last@domain, then first.last2@domain, first.last3@domain, …
     *
     * @return iterable<int, string>
     */
    public function candidates(Member $member, string $domain): iterable
    {
        $local = Str::of((string) $member->full_name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z\s]/', '')
            ->squish()
            ->replace(' ', '.')
            ->toString();

        $local = $local !== '' ? $local : 'member';

        yield "{$local}@{$domain}";

        for ($suffix = 2; $suffix <= self::MAX_CANDIDATES; $suffix++) {
            yield "{$local}{$suffix}@{$domain}";
        }
    }

    private function provisionOrganisationUser(Member $member): User
    {
        foreach ($this->candidates($member, $this->organisationDomain()) as $email) {
            if (!$this->isAvailable($email)) {
                continue;
            }

            try {
                // The unique index on users.email is the reservation lock. A savepoint keeps the
                // outer transaction usable on Postgres when another request claimed the address first.
                $user = User::query()
                    ->getConnection()
                    ->transaction(fn() => User::create([
                        'name' => $member->full_name,
                        'email' => $email,
                        'password' => Utils::randomPassword(),
                    ]));
            } catch (UniqueConstraintViolationException) {
                continue;
            }

            $member->update([
                'user_id' => $user->id,
                'email' => $email,
                'workspace_status' => PRFWorkspaceStatus::PENDING,
            ]);

            return $user;
        }

        throw new RuntimeException("No free organisation address left for member {$member->ulid}.");
    }

    private function provisionPersonalUser(Member $member): User
    {
        $email = strtolower(trim((string) $member->personal_email));

        if ($email === '') {
            throw new RuntimeException("Member {$member->ulid} has no personal email to sign in with.");
        }

        $user = User::withTrashed()->where('email', $email)->first();

        if ($user?->trashed()) {
            $user->restore();
        }

        $user ??= User::create([
            'name' => $member->full_name,
            'email' => $email,
            'password' => Utils::randomPassword(),
        ]);

        $member->update([
            'user_id' => $user->id,
            'email' => $email,
            'workspace_status' => PRFWorkspaceStatus::NOT_APPLICABLE,
        ]);

        return $user;
    }

    private function organisationDomain(): string
    {
        return Utils::getOrgEmailDomain() ?? throw new RuntimeException(
            'This organisation has no Google Workspace domain configured.',
        );
    }
}
