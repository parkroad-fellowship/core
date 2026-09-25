<?php

namespace App\Listeners\Requisition;

use App\Events\Requisition\RequisitionRecalled;
use App\Notifications\Requisition\RequisitionRecalledNotification;
use Illuminate\Support\Facades\Notification;

class NotifyStakeholdersOfRecall
{
    public function handle(RequisitionRecalled $event): void
    {
        Notification::send(
            $event->requisition->stakeholders(
                includeRequester: true,
                includeTreasury: true,
                formerApproverId: $event->formerApproverId,
            ),
            new RequisitionRecalledNotification(requisition: $event->requisition),
        );
    }
}
