<?php

namespace App\Listeners\Member;

use App\Contracts\Services\WorkspaceDirectoryInterface;
use App\Enums\PRFWorkspaceStatus;
use App\Events\Member\MemberContactChanged;
use App\Helpers\Utils;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Keeps a Workspace mailbox's recovery email and phone pointing at the member's own contacts.
 */
class UpdateWorkspaceRecovery implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly WorkspaceDirectoryInterface $directory,
    ) {}

    public function handle(MemberContactChanged $event): void
    {
        $member = $event->member;

        if ($member->workspace_status !== PRFWorkspaceStatus::PROVISIONED || blank($member->email)) {
            return;
        }

        $this->directory->updateRecovery(
            (string) $member->email,
            $member->personal_email,
            Utils::toE164($member->phone_number),
        );
    }
}
