<?php

namespace App\Contracts\Services;

interface SpeechToTextServiceInterface
{
    /**
     * Submit an audio file for transcription.
     *
     * @param  string[]  $contentUrls
     * @return array{self?: string, status?: string, id?: string}
     */
    public function transcribe(array $contentUrls, string $displayName): array;

    /**
     * Get the status of a transcription job.
     *
     * @return array{status: string, links?: array}
     */
    public function getTranscriptionStatus(string $statusUrl): array;

    /**
     * Get the transcription files for a completed job.
     *
     * @return array{values?: array}
     */
    public function getTranscriptionFiles(string $filesUrl): array;
}
