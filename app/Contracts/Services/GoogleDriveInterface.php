<?php

namespace App\Contracts\Services;

use App\Models\Mission;

interface GoogleDriveInterface
{
    /**
     * Upload mission files to Google Drive.
     *
     * @param  array<int, array{name: string, url: string}>  $mediaFiles
     * @return array{success: bool, uploaded_files?: array, errors?: array, mission_folder_id?: string}
     */
    public function uploadMissionFiles(Mission $mission, array $mediaFiles): array;

    /**
     * Test the connection to Google Drive.
     *
     * @return array{success: bool, user_email?: string, error?: string}
     */
    public function testConnection(): array;

    /**
     * List available shared drives.
     *
     * @return array{success: bool, drives?: array, error?: string}
     */
    public function listSharedDrives(): array;

    /**
     * Create the main missions folder if it doesn't exist.
     */
    public function createMissionsFolder(): string;
}
