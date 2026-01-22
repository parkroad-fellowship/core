<?php

namespace App\Services;

use Exception;
use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Illuminate\Support\Facades\Log;

class GoogleSheetsService
{
    private Google_Service_Sheets $sheetsService;

    private string $spreadsheetId;

    private string $sheetName;

    public function __construct()
    {
        $this->spreadsheetId = config('prf.hooks.google_sheets.spreadsheet_id');
        $this->sheetName = config('prf.hooks.google_sheets.sheet_name');

        $this->initializeGoogleClient();
    }

    private function initializeGoogleClient(): void
    {
        $client = new Google_Client;
        $client->setApplicationName('PRF Social Media Integration');

        // Set up service account authentication
        $keyPath = config('prf.hooks.google_sheets.service_account_key_path');
        if (! $keyPath || ! file_exists($keyPath)) {
            throw new Exception('Google service account key file not found: '.$keyPath);
        }

        $client->setAuthConfig($keyPath);
        $client->addScope(Google_Service_Sheets::SPREADSHEETS);

        $this->sheetsService = new Google_Service_Sheets($client);
    }

    /**
     * Add a row to the Google Sheet to trigger Zapier workflow
     */
    public function addSocialMediaPost(array $postData): bool
    {
        try {
            // Prepare the row data with all social media platform fields
            $values = [
                [
                    date('Y-m-d H:i:s'), // A - Timestamp
                    $postData['mission_id'] ?? '', // B - Mission ID
                    $postData['title'] ?? '', // C - Title/Caption
                    $postData['content'] ?? '', // D - Content/Description
                    $postData['media_url'] ?? '', // E - Media URL
                    $postData['school_name'] ?? '', // F - School Name
                    $postData['mission_type'] ?? '', // G - Mission Type
                    $postData['scheduled_for'] ?? '', // H - Scheduled For
                    'pending', // I - Status

                    // Instagram specific
                    $postData['instagram_caption'] ?? ($postData['content'] ?? ''), // J - Instagram Caption
                    $postData['instagram_alt_text'] ?? '', // K - Instagram Alt Text
                    $postData['instagram_location'] ?? '', // L - Instagram Location
                    $postData['instagram_hashtags'] ?? '#missions #church #community', // M - Instagram Hashtags

                    // Facebook specific
                    $postData['facebook_message'] ?? ($postData['content'] ?? ''), // N - Facebook Message
                    $postData['facebook_link'] ?? '', // O - Facebook Link
                    $postData['facebook_link_description'] ?? '', // P - Facebook Link Description
                    $postData['facebook_place_id'] ?? '', // Q - Facebook Place ID

                    // YouTube specific
                    $postData['youtube_title'] ?? ($postData['title'] ?? ''), // R - YouTube Title
                    $postData['youtube_description'] ?? ($postData['content'] ?? ''), // S - YouTube Description
                    $postData['youtube_tags'] ?? 'missions,church,community,faith', // T - YouTube Tags
                    $postData['youtube_category'] ?? '22', // U - YouTube Category (People & Blogs)
                    $postData['youtube_privacy'] ?? 'public', // V - YouTube Privacy
                    $postData['youtube_thumbnail'] ?? '', // W - YouTube Thumbnail URL

                    // TikTok specific
                    $postData['tiktok_caption'] ?? ($postData['content'] ?? ''), // X - TikTok Caption
                    $postData['tiktok_hashtags'] ?? '#missions #church #faith #community', // Y - TikTok Hashtags
                    $postData['tiktok_privacy'] ?? 'public', // Z - TikTok Privacy
                    $postData['tiktok_allow_comments'] ?? 'true', // AA - TikTok Allow Comments
                    $postData['tiktok_allow_duet'] ?? 'true', // AB - TikTok Allow Duet
                    $postData['tiktok_allow_stitch'] ?? 'true', // AC - TikTok Allow Stitch

                    // Threads specific
                    $postData['threads_text'] ?? ($postData['content'] ?? ''), // AD - Threads Text
                    $postData['threads_reply_to'] ?? '', // AE - Threads Reply To
                    $postData['threads_reply_control'] ?? 'everyone', // AF - Threads Reply Control

                    // General platform settings
                    $postData['platforms'] ?? 'instagram,facebook,youtube,tiktok,threads', // AG - Target Platforms
                    $postData['priority'] ?? 'normal', // AH - Priority
                    $postData['campaign'] ?? '', // AI - Campaign Name
                    $postData['notes'] ?? '', // AJ - Internal Notes

                    // Instagram Images (Image 1 - Image 10) at the end
                    $postData['image_1'] ?? '', // AK - Instagram Image 1
                    $postData['image_2'] ?? '', // AL - Instagram Image 2
                    $postData['image_3'] ?? '', // AM - Instagram Image 3
                    $postData['image_4'] ?? '', // AN - Instagram Image 4
                    $postData['image_5'] ?? '', // AO - Instagram Image 5
                    $postData['image_6'] ?? '', // AP - Instagram Image 6
                    $postData['image_7'] ?? '', // AQ - Instagram Image 7
                    $postData['image_8'] ?? '', // AR - Instagram Image 8
                    $postData['image_9'] ?? '', // AS - Instagram Image 9
                    $postData['image_10'] ?? '', // AT - Instagram Image 10
                ],
            ];

            $range = $this->sheetName.'!A:AT'; // Columns A through AT (46 columns)
            $valueRange = new Google_Service_Sheets_ValueRange;
            $valueRange->setValues($values);

            $params = [
                'valueInputOption' => 'RAW',
                'insertDataOption' => 'INSERT_ROWS',
            ];

            $result = $this->sheetsService->spreadsheets_values->append(
                $this->spreadsheetId,
                $range,
                $valueRange,
                $params
            );

            Log::info('Successfully added row to Google Sheets', [
                'mission_id' => $postData['mission_id'] ?? null,
                'updates' => $result->getUpdates(),
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Failed to add row to Google Sheets', [
                'mission_id' => $postData['mission_id'] ?? null,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to add social media post to Google Sheets: '.$e->getMessage());
        }
    }

    /**
     * Create the headers for the Google Sheet (run this once to set up the sheet)
     */
    public function createHeaders(): bool
    {
        try {
            $headers = [
                [
                    // Basic Info (A-I)
                    'Timestamp', // A
                    'Mission ID', // B
                    'Title', // C
                    'Content', // D
                    'Media URL', // E
                    'School Name', // F
                    'Mission Type', // G
                    'Scheduled For', // H
                    'Status', // I

                    // Instagram (J-M)
                    'Instagram Caption', // J
                    'Instagram Alt Text', // K
                    'Instagram Location', // L
                    'Instagram Hashtags', // M

                    // Facebook (N-Q)
                    'Facebook Message', // N
                    'Facebook Link', // O
                    'Facebook Link Description', // P
                    'Facebook Place ID', // Q

                    // YouTube (R-W)
                    'YouTube Title', // R
                    'YouTube Description', // S
                    'YouTube Tags', // T
                    'YouTube Category', // U
                    'YouTube Privacy', // V
                    'YouTube Thumbnail', // W

                    // TikTok (X-AC)
                    'TikTok Caption', // X
                    'TikTok Hashtags', // Y
                    'TikTok Privacy', // Z
                    'TikTok Allow Comments', // AA
                    'TikTok Allow Duet', // AB
                    'TikTok Allow Stitch', // AC

                    // Threads (AD-AF)
                    'Threads Text', // AD
                    'Threads Reply To', // AE
                    'Threads Reply Control', // AF

                    // General Settings (AG-AJ)
                    'Target Platforms', // AG
                    'Priority', // AH
                    'Campaign', // AI
                    'Notes', // AJ

                    // Instagram Images (AK-AT) at the end
                    'Instagram Image 1', // AK
                    'Instagram Image 2', // AL
                    'Instagram Image 3', // AM
                    'Instagram Image 4', // AN
                    'Instagram Image 5', // AO
                    'Instagram Image 6', // AP
                    'Instagram Image 7', // AQ
                    'Instagram Image 8', // AR
                    'Instagram Image 9', // AS
                    'Instagram Image 10', // AT
                ],
            ];

            $range = $this->sheetName.'!A1:AT1';
            $valueRange = new Google_Service_Sheets_ValueRange;
            $valueRange->setValues($headers);

            $params = ['valueInputOption' => 'RAW'];

            $this->sheetsService->spreadsheets_values->update(
                $this->spreadsheetId,
                $range,
                $valueRange,
                $params
            );

            Log::info('Successfully created headers in Google Sheets');

            return true;

        } catch (Exception $e) {
            Log::error('Failed to create headers in Google Sheets', [
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to create headers in Google Sheets: '.$e->getMessage());
        }
    }

    /**
     * Test the connection to Google Sheets
     */
    public function testConnection(): array
    {
        try {
            $spreadsheet = $this->sheetsService->spreadsheets->get($this->spreadsheetId);

            return [
                'success' => true,
                'title' => $spreadsheet->getProperties()->getTitle(),
                'sheet_count' => count($spreadsheet->getSheets()),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
