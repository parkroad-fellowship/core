<?php

namespace App\Jobs\NLP;

use App\Contracts\Services\NLPServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
    public function handle(NLPServiceInterface $nlp): void
    {
        $nlp->embedContent($this->documents);
    }
}
