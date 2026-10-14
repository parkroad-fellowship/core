<?php

namespace App\Listeners\Member;

use App\Contracts\Services\WorkspaceDirectoryInterface;
use App\Enums\PRFWorkspaceStatus;
use App\Events\Member\MemberRestored;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;

class UnsuspendWorkspaceAccount implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly WorkspaceDirectoryInterface $directory,
    ) {}

    public function handle(MemberRestored $event): void
    {
        $member = $event->member;

        if ($member->workspace_status !== PRFWorkspaceStatus::SUSPENDED || blank($member->email)) {
            return;
        }

        $this->directory->unsuspend($member->email);

        $member->updateQuietly(['workspace_status' => PRFWorkspaceStatus::PROVISIONED]);
    }
}
