<?php

namespace App\Listeners\Requisition;

use App\Events\Requisition\RequisitionRejected;
use App\Notifications\Requisition\RequisitionRejectedNotification;
use Illuminate\Support\Facades\Notification;

class NotifyStakeholdersOfRejection
{
    public function handle(RequisitionRejected $event): void
    {
        Notification::send(
            $event->requisition->stakeholders(),
            new RequisitionRejectedNotification($event->requisition),
        );
    }
}
