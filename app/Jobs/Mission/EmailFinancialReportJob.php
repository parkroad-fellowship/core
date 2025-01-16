<?php

namespace App\Jobs\Mission;

use App\Exports\MissionExpense\Report;
use App\Models\Mission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
            ->append('-financial-report.xlsx')
            ->__toString();

        // Generate the financial report and save it to a file
        Excel::store(
            export: new Report(
                missionExpenseId: $mission->missionExpense->id,
            ),
            filePath: $fileName,
        );

        // Get file link from S3 bucket
        $fileLink = Storage::url($fileName);

        // Send the financial report to the treasurer

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Key '.config('services.onesignal.rest_api_key'),
        ])
            ->withQueryParameters([
                'c' => 'email', // Channel
            ])
            ->post('https://api.onesignal.com/notifications', [
                'app_id' => config('services.onesignal.app_id'),
                'email_subject' => 'Financial Report: '.$this->mission->school->name,
                'include_email_tokens' => [
                    'treasurer@parkroadfellowship.org',
                    'missions@parkroadfellowship.org',
                ],
                'email_from_name' => config('mail.from.name'),
                'email_body' => (new HtmlString(view('emails.missions.financials', [
                    'mission' => $this->mission,
                    'link' => $fileLink,
                ])->render()))->__toString(),
            ]);

        Log::info($response->json());
    }
}
