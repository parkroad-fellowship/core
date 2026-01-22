<?php

namespace App\Services;

use App\Helpers\Utils;
use App\Models\Mission;
use Exception;
use Google\Client as Google_Client;
use Google\Service\Drive as Google_Service_Drive;
use Google\Service\Drive\DriveFile as Google_Service_Drive_DriveFile;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    private Google_Service_Drive $driveService;

    private string $folderId;

    private ?string $sharedDriveId;

    public function __construct()
    {
        $this->folderId = config('prf.hooks.google_drive.folder_id');
        $this->sharedDriveId = config('prf.hooks.google_drive.shared_drive_id');
        $this->initializeGoogleClient();
    }

    private function initializeGoogleClient(): void
    {
        $client = new Google_Client;
        $client->setApplicationName('PRF Mission Files Upload');

        // Set up service account authentication using the same key as Google Sheets
        $keyPath = config('prf.hooks.google_sheets.service_account_key_path');
        if (! $keyPath || ! file_exists($keyPath)) {
            throw new Exception('Google service account key file not found: '.$keyPath);
        }

        $client->setAuthConfig($keyPath);
        $client->addScope([
            Google_Service_Drive::DRIVE_FILE,
            Google_Service_Drive::DRIVE,
        ]);

        $this->driveService = new Google_Service_Drive($client);
    }

    /**
     * Upload mission files (photos and videos) to Google Drive
     */
    public function uploadMissionFiles(Mission $mission, array $mediaFiles): array
    {
        $uploadedFiles = [];
        $errors = [];

        try {
            // Create or get mission folder
            $missionFolderId = $this->createMissionFolder(
                year: $mission->start_date->format('Y'),
                month: $mission->start_date->format('m'),
                name: Utils::generateMissionName($mission)
            );

            foreach ($mediaFiles as $mediaFile) {
                try {
                    $uploadedFile = $this->uploadFile($mediaFile, $missionFolderId);
                    $uploadedFiles[] = $uploadedFile;

                    Log::info('Successfully uploaded file to Google Drive', [
                        'mission_id' => $mission->id,
                        'file_name' => $mediaFile['name'],
                        'drive_file_id' => $uploadedFile['id'],
                    ]);
                } catch (Exception $e) {
                    $errors[] = [
                        'file_name' => $mediaFile['name'],
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to upload file to Google Drive', [
                        'mission_id' => $mission->id,
                        'file_name' => $mediaFile['name'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return [
                'success' => true,
                'uploaded_files' => $uploadedFiles,
                'errors' => $errors,
                'mission_folder_id' => $missionFolderId,
            ];

        } catch (Exception $e) {
            Log::error('Failed to upload mission files to Google Drive', [
                'mission_id' => $mission->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to upload mission files to Google Drive: '.$e->getMessage());
        }
    }

    /**
     * Create the complete folder structure for the mission
     * Structure: year/month/mission-name/raw-files
     */
    private function createMissionFolder(
        int $year,
        int $month,
        string $name,
    ): string {
        // Create year folder
        $yearFolder = $this->createOrGetFolder($year, $this->folderId);

        // Create month folder inside year
        $monthName = str_pad($month, 2, '0', STR_PAD_LEFT); // Format as 01, 02, etc.
        $monthFolder = $this->createOrGetFolder($monthName, $yearFolder);

        // Create mission folder inside month
        $missionFolder = $this->createOrGetFolder($name, $monthFolder);

        // Create raw-files folder inside mission
        $rawFilesFolder = $this->createOrGetFolder('raw-files', $missionFolder);

        Log::info('Created mission folder structure in Google Drive', [
            'year' => $year,
            'month' => $monthName,
            'mission_name' => $name,
            'raw_files_folder_id' => $rawFilesFolder,
        ]);

        return $rawFilesFolder;
    }

    /**
     * Create or get an existing folder
     */
    private function createOrGetFolder(string $folderName, string $parentFolderId): string
    {
        // Check if folder already exists
        $existingFolder = $this->findFolderByName($folderName, $parentFolderId);
        if ($existingFolder) {
            return $existingFolder->getId();
        }

        // Create new folder
        $folderMetadata = new Google_Service_Drive_DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentFolderId],
        ]);

        $createOptions = [
            'fields' => 'id',
        ];

        // Add shared drive support if configured
        if ($this->sharedDriveId) {
            $createOptions['supportsAllDrives'] = true;
        }

        $folder = $this->driveService->files->create($folderMetadata, $createOptions);

        Log::info('Created folder in Google Drive', [
            'folder_name' => $folderName,
            'parent_folder_id' => $parentFolderId,
            'folder_id' => $folder->getId(),
            'shared_drive_id' => $this->sharedDriveId,
        ]);

        return $folder->getId();
    }

    /**
     * Upload a single file to Google Drive
     */
    private function uploadFile(array $mediaFile, string $parentFolderId): array
    {
        // Get file content from URL
        $fileContent = $this->downloadFileFromUrl($mediaFile['url']);

        // Determine file extension and MIME type
        $extension = pathinfo($mediaFile['name'], PATHINFO_EXTENSION);
        $mimeType = $this->getMimeTypeFromExtension($extension);

        // Prepare file metadata
        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $mediaFile['name'],
            'parents' => [$parentFolderId],
        ]);

        $uploadOptions = [
            'data' => $fileContent,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id,name,webViewLink,webContentLink',
        ];

        // Add shared drive support if configured
        if ($this->sharedDriveId) {
            $uploadOptions['supportsAllDrives'] = true;
        }

        // Upload file
        $uploadedFile = $this->driveService->files->create($fileMetadata, $uploadOptions);

        return [
            'id' => $uploadedFile->getId(),
            'name' => $uploadedFile->getName(),
            'web_view_link' => $uploadedFile->getWebViewLink(),
            'web_content_link' => $uploadedFile->getWebContentLink(),
            'original_url' => $mediaFile['url'],
            'mime_type' => $mimeType,
        ];
    }

    /**
     * Download file content from URL
     */
    private function downloadFileFromUrl(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'method' => 'GET',
            ],
        ]);

        $fileContent = file_get_contents($url, false, $context);

        if ($fileContent === false) {
            throw new Exception("Failed to download file from URL: {$url}");
        }

        return $fileContent;
    }

    /**
     * Get MIME type from file extension
     */
    private function getMimeTypeFromExtension(string $extension): string
    {
        $mimeTypes = [
            // Images
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'webp' => 'image/webp',

            // Videos
            'mp4' => 'video/mp4',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'wmv' => 'video/x-ms-wmv',
            'flv' => 'video/x-flv',
            'webm' => 'video/webm',
            'mkv' => 'video/x-matroska',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
        ];

        $extension = strtolower($extension);

        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Find folder by name in parent folder
     */
    private function findFolderByName(string $folderName, string $parentFolderId): ?Google_Service_Drive_DriveFile
    {
        $query = "name='{$folderName}' and mimeType='application/vnd.google-apps.folder' and '{$parentFolderId}' in parents and trashed=false";

        $listOptions = [
            'q' => $query,
            'fields' => 'files(id,name)',
        ];

        // Add shared drive support if configured
        if ($this->sharedDriveId) {
            $listOptions['supportsAllDrives'] = true;
            $listOptions['includeItemsFromAllDrives'] = true;
        }

        $response = $this->driveService->files->listFiles($listOptions);

        $files = $response->getFiles();

        return count($files) > 0 ? $files[0] : null;
    }

    /**
     * Test the connection to Google Drive
     */
    public function testConnection(): array
    {
        try {
            $about = $this->driveService->about->get(['fields' => 'user']);

            $result = [
                'success' => true,
                'user_email' => $about->getUser()->getEmailAddress(),
                'user_name' => $about->getUser()->getDisplayName(),
                'using_shared_drive' => ! empty($this->sharedDriveId),
            ];

            // If using shared drive, get shared drive info
            if ($this->sharedDriveId) {
                try {
                    $sharedDrive = $this->driveService->drives->get($this->sharedDriveId);
                    $result['shared_drive_name'] = $sharedDrive->getName();
                    $result['shared_drive_id'] = $this->sharedDriveId;
                } catch (Exception $e) {
                    $result['shared_drive_error'] = 'Cannot access shared drive: '.$e->getMessage();
                }
            }

            return $result;

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * List available shared drives
     */
    public function listSharedDrives(): array
    {
        try {
            $drives = $this->driveService->drives->listDrives();

            $result = [];
            foreach ($drives->getDrives() as $drive) {
                $result[] = [
                    'id' => $drive->getId(),
                    'name' => $drive->getName(),
                    'created_time' => $drive->getCreatedTime(),
                ];
            }

            return [
                'success' => true,
                'drives' => $result,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create the main missions folder if it doesn't exist
     */
    public function createMissionsFolder(): string
    {
        $folderName = 'Missions';

        // Check if folder already exists at root level or in shared drive
        $query = "name='{$folderName}' and mimeType='application/vnd.google-apps.folder' and trashed=false";

        $listOptions = [
            'q' => $query,
            'fields' => 'files(id,name)',
        ];

        // Add shared drive support if configured
        if ($this->sharedDriveId) {
            $listOptions['supportsAllDrives'] = true;
            $listOptions['includeItemsFromAllDrives'] = true;
            $listOptions['driveId'] = $this->sharedDriveId;
            $listOptions['corpora'] = 'drive';
        }

        $response = $this->driveService->files->listFiles($listOptions);

        $files = $response->getFiles();

        if (count($files) > 0) {
            return $files[0]->getId();
        }

        // Create new folder at root level or in shared drive
        $folderMetadata = new Google_Service_Drive_DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);

        // If using shared drive, set the parent to the shared drive root
        if ($this->sharedDriveId) {
            $folderMetadata->setParents([$this->sharedDriveId]);
        }

        $createOptions = [
            'fields' => 'id',
        ];

        // Add shared drive support if configured
        if ($this->sharedDriveId) {
            $createOptions['supportsAllDrives'] = true;
        }

        $folder = $this->driveService->files->create($folderMetadata, $createOptions);

        Log::info('Created main missions folder in Google Drive', [
            'folder_name' => $folderName,
            'folder_id' => $folder->getId(),
            'shared_drive_id' => $this->sharedDriveId,
        ]);

        return $folder->getId();
    }
}
