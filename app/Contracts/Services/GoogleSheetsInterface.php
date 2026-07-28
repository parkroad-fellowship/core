<?php

namespace App\Contracts\Services;

interface GoogleSheetsInterface
{
    /**
     * Add a social media post row to the Google Sheet.
     */
    public function addSocialMediaPost(array $postData): bool;

    /**
     * Create headers for the Google Sheet.
     */
    public function createHeaders(): bool;

    /**
     * Test the connection to Google Sheets.
     *
     * @return array{success: bool, title?: string, sheet_count?: int, error?: string}
     */
    public function testConnection(): array;
}
