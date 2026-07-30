<?php

namespace App\Contracts\Services;

interface AIServiceInterface
{
    /**
     * Generate content using an AI model.
     *
     * @return array{candidates?: array, error?: string}
     */
    public function generateContent(string $systemPrompt, string $userPrompt): array;
}
