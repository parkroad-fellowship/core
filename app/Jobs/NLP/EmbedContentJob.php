<?php

namespace App\Jobs\NLP;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmbedContentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected array $documents
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $response = Http::withHeaders([
            'x-token' => config('prf.nlp.api_key'),
        ])->post(config('prf.nlp.base_url').'/embedding/init', [
            'texts' => $this->documents,
        ]);

        if ($response->successful()) {
            // Log success
            Log::info('Content embedding successful for '.count($this->documents).' texts.');
            Log::info('Response: ', ['response' => $response->json()]);
        } else {
            // Log failure
            Log::error('Content embedding failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

    }
}
