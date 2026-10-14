<?php

namespace App\Contracts\Services;

use App\AI\AIPrompt;

/**
 * Provider-agnostic text generation using the current tenant's AI settings.
 */
interface AIServiceInterface
{
    public function text(AIPrompt $prompt): string;

    /**
     * Generate a JSON response and decode it.
     *
     * @return array<array-key, mixed>
     */
    public function structured(AIPrompt $prompt): array;
}
