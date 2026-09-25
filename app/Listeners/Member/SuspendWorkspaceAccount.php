<?php

namespace App\Listeners\Member;

use App\Contracts\Services\WorkspaceDirectoryInterface;
use App\Enums\PRFWorkspaceStatus;
use App\Events\Member\MemberDeleted;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Removing a member suspends (never deletes) their Workspace mailbox.
 */
class SuspendWorkspaceAccount implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly WorkspaceDirectoryInterface $directory,
    ) {}

    public function handle(MemberDeleted $event): void
    {
        $member = $event->member;

        if ($member->workspace_status !== PRFWorkspaceStatus::PROVISIONED || blank($member->email)) {
            return;
        }

        $this->directory->suspend($member->email);

        $member->updateQuietly(['workspace_status' => PRFWorkspaceStatus::SUSPENDED]);
    }
}
