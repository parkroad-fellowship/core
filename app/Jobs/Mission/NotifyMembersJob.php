<?php

namespace App\Jobs\Mission;

use App\Models\Member;
use App\Models\Mission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class NotifyMembersJob implements ShouldQueue
{
    use Dispatchable;
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
        $mission->load(['school', 'missionType']);

        Member::query()
            ->chunk(30, function ($members) {
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
                        'email_subject' => '(Test New Mission) '.$this->mission->school->name,
                        'include_email_tokens' => $members->pluck('email')->toArray(),
                        'email_from_name' => config('mail.from.name'),
                        'email_body' => (new HtmlString(view('emails.missions.new', ['mission' => $this->mission])->render()))->__toString(),
                    ]);

                Log::info($response->json());
            });
    }
}
