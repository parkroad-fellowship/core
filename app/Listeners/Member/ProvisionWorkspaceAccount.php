<?php

namespace App\Listeners\Member;

use App\Enums\PRFWorkspaceStatus;
use App\Events\Member\MemberOnboarded;
use App\Jobs\Member\ProvisionWorkspaceAccountJob;

class ProvisionWorkspaceAccount
{
    public function handle(MemberOnboarded $event): void
    {
        if ($event->member->workspace_status === PRFWorkspaceStatus::PENDING) {
            ProvisionWorkspaceAccountJob::dispatch($event->member);
        }
    }
}
