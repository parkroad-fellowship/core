<?php

namespace App\Jobs\ChatBot;

use App\Models\ChatBot;
use Illuminate\Foundation\Bus\Dispatchable;

class CreateJob
{
    use Dispatchable;

    public function __construct(
        public array $data,
    ) {}

    public function handle(): ChatBot
    {
        return ChatBot::create($this->data);
    }
}
