<?php

namespace App\AI;

/**
 * A provider-neutral request to the tenant's AI model.
 */
final readonly class AIPrompt
{
    /**
     * @param  string  $feature  used to look up an optional per-feature model (setting `ai.models.{feature}`)
     */
    public function __construct(
        public string $systemPrompt,
        public string $userPrompt,
        public string $feature = 'default',
        public ?int $maxTokens = null,
        public int $timeout = 240,
    ) {}
}
