<?php

namespace App\AI\Agents;

use App\AI\AIPrompt;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

/**
 * Generic agent that carries one AIPrompt's instructions and token budget.
 */
final class PromptAgent implements Agent
{
    use Promptable;

    public function __construct(
        private readonly AIPrompt $prompt,
        private readonly int $defaultMaxTokens,
    ) {}

    public function instructions(): string
    {
        return $this->prompt->systemPrompt;
    }

    public function maxTokens(): int
    {
        return $this->prompt->maxTokens ?? $this->defaultMaxTokens;
    }
}
