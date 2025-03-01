<?php

namespace App\Jobs\Mission;

use App\Exports\MissionExpense\Report;
use App\Models\Member;
use App\Models\Mission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
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

        $fileName = Str::of($mission->school->name)
            ->append('-')
            ->append($mission->start_date->format('Y-m-d'))
            ->append('-financial-report')
            ->slug()
            ->append('.xlsx')
            ->__toString();

        // Generate the financial report and save it to a file
        Excel::store(
            export: new Report(
                missionExpenseId: $mission->missionExpense->id,
            ),
            filePath: $fileName,
        );

        // Get file link from S3 bucket
        // $fileLink = Storage::url($fileName);

        // Send the financial report to the treasurer
        $officials = Member::query()
            ->whereIn('email', [
                'treasurer@parkroadfellowship.org',
                'missions@parkroadfellowship.org',
                'adulu@parkroadfellowship.org'
            ])
            ->get();

        Notification::send(
            $officials,
            new \App\Notifications\Mission\FinancialsNotification(
                mission: $mission,
                fileName: $fileName,
            ),
        );
    }
}
