<?php

namespace App\Listeners\Mission;

use App\Events\Mission\MissionCancelled;
use App\Events\Mission\MissionPostponed;
use App\Events\Mission\MissionServiced;
use App\Jobs\AccountingEvent\EmailFinancialReportJob;

class EmailMissionFinancialReport
{
    public function handle(MissionServiced|MissionPostponed|MissionCancelled $event): void
    {
        // Missions that never reached approval have no accounting event to report on.
        $accountingEvent = $event->mission->accountingEvent;

        if ($accountingEvent !== null) {
            EmailFinancialReportJob::dispatch($accountingEvent->ulid);
        }
    }
}
