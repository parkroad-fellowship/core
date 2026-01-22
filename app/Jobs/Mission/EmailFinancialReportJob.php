<?php

namespace App\Jobs\Mission;

use App\Notifications\Mission\FinancialsNotification;
use App\Enums\PRFResponsibleDesk;
use App\Exports\MissionExpense\Report;
use App\Helpers\Utils;
use App\Models\Member;
use App\Models\Mission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

class EmailFinancialReportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mission $mission,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mission = $this->mission;
        $mission->load([
            'school',
            'missionExpense',
        ]);

        $fileName = Utils::generateMissionFileName(
            mission: $mission,
            type: 'financial',
            extension: '.xlsx'
        );

        if (! $mission->missionExpense) {
            return;
        }

        // Generate the financial report and save it to a file
        Excel::store(
            export: new Report(
                missionExpenseId: $mission->missionExpense->id,
            ),
            filePath: $fileName,
        );

        // Send the financial report to the treasurer
        $officials = Member::query()
            ->whereIn('email', Utils::getDeskEmails(PRFResponsibleDesk::TREASURER_DESK))
            ->get();

        Notification::send(
            $officials,
            new FinancialsNotification(
                mission: $mission,
                fileName: $fileName,
            ),
        );
    }
}
