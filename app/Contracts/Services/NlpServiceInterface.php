<?php

namespace App\Contracts\Services;

interface NlpServiceInterface
{
    /**
     * Embed content for vector search.
     *
     * @param  array<int, string>  $documents
     */
    public function embedContent(array $documents): void;

    /**
     * Enquire the NLP chatbot with a question.
     *
     * @param  array<int, array{role: string, content: string}>  $conversationHistory
     * @return array{answer: string, meta?: array}
     */
    public function enquire(string $question, array $conversationHistory): array;
}
