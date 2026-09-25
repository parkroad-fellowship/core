<?php

namespace App\Jobs\NLP;

use App\Contracts\Services\NLPServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;

#[Queue('long')]
#[Tries(3)]
class EmbedContentJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected array $documents,
    ) {}

    public function handle(NLPServiceInterface $nlp): void
    {
        $nlp->embedContent($this->documents);
    }
}
