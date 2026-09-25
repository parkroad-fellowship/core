<?php

namespace App\Jobs\Member;

use App\Contracts\Services\WorkspaceDirectoryInterface;
use App\Enums\PRFIntegration;
use App\Enums\PRFMemberEmailMode;
use App\Enums\PRFWorkspaceStatus;
use App\Exceptions\WorkspaceUserAlreadyExistsException;
use App\Models\Member;
use App\Notifications\Member\MemberCredentialsIssuedNotification;
use App\Services\Google\Workspace\WorkspaceUserData;
use App\Services\Members\MemberIdentityService;
use App\Services\Tenancy\TenantIntegrations;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Creates the member's mailbox in the tenant's Google Workspace. If Workspace reports the
 * address is taken, the member moves to the next free address and the job tries again.
 */
#[Queue('high')]
#[Tries(3)]
#[Backoff([60, 300])]
class ProvisionWorkspaceAccountJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    private const MAX_CONFLICTS = 5;

    public function __construct(
        public Member $member,
    ) {}

    public function uniqueId(): string
    {
        return $this->member->ulid;
    }

    public function handle(
        WorkspaceDirectoryInterface $directory,
        MemberIdentityService $identity,
        TenantIntegrations $integrations,
    ): void {
        $member = $this->member->fresh() ?? $this->member;

        if (
            $identity->mode() !== PRFMemberEmailMode::ORGANISATION_DOMAIN
            || $member->workspace_status === PRFWorkspaceStatus::PROVISIONED
        ) {
            return;
        }

        if (!$integrations->isConfigured(PRFIntegration::GOOGLE_WORKSPACE)) {
            $member->update([
                'workspace_status' => PRFWorkspaceStatus::FAILED,
                'workspace_error' => PRFIntegration::GOOGLE_WORKSPACE->getLabel() . ' is not configured.',
            ]);

            return;
        }

        $password = Str::password(16, symbols: false);

        for ($attempt = 1; $attempt <= self::MAX_CONFLICTS; $attempt++) {
            try {
                $workspaceUser = $directory->create(new WorkspaceUserData(
                    primaryEmail: (string) $member->email,
                    givenName: (string) $member->first_name,
                    familyName: (string) $member->last_name,
                    password: $password,
                    orgUnitPath: (string) config('prf.google_workspace.org_unit_path', '/'),
                ));
            } catch (WorkspaceUserAlreadyExistsException) {
                $identity->reallocateOrganisationEmail($member);

                continue;
            }

            $member->update([
                'workspace_user_id' => $workspaceUser->id,
                'workspace_status' => PRFWorkspaceStatus::PROVISIONED,
                'workspace_error' => null,
                'workspace_provisioned_at' => now(),
            ]);

            // Sent synchronously so the temporary password is never written to the queue.
            Notification::route('mail', $member->personal_email)->notifyNow(new MemberCredentialsIssuedNotification(
                $member,
                $password,
            ));

            return;
        }

        throw new RuntimeException("Workspace kept reporting conflicts for member {$member->ulid}.");
    }

    public function failed(Throwable $exception): void
    {
        $this->member->update([
            'workspace_status' => PRFWorkspaceStatus::FAILED,
            'workspace_error' => Str::limit($exception->getMessage(), 500),
        ]);
    }
}
