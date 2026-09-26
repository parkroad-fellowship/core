<?php

namespace App\Listeners\Requisition;

use App\Events\Requisition\RequisitionReviewRequested;
use App\Notifications\Requisition\RequisitionReviewRequestedNotification;

class NotifyAppointedApprover
{
    public function handle(RequisitionReviewRequested $event): void
    {
        $event->appointedApprover->notify(new RequisitionReviewRequestedNotification($event->requisition));
    }
}
