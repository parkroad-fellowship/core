<?php

namespace App\Jobs\ChatBot;

use App\Models\ChatBot;
use Illuminate\Foundation\Bus\Dispatchable;

class UpdateJob
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $data,
        public string $ulid,
    ) {}

    public function handle(): ChatBot
    {
        $chatBot = ChatBot::query()->where('ulid', $this->ulid)->firstOrFail();

        $attributes = $this->data;

        $chatBot->update($attributes);

        return $chatBot;
    }
}
