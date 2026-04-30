<?php

namespace App\Jobs\ChatBot;

use App\Models\ChatBot;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $data,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): ChatBot
    {
        return ChatBot::create($this->data);
    }
}
