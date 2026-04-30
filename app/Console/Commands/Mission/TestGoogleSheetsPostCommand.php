<?php

namespace App\Console\Commands\Mission;

use App\Services\GoogleSheetsService;
use Exception;
use Illuminate\Console\Command;

class TestGoogleSheetsPostCommand extends Command
{
    protected $signature = 'mission:test-google-sheets-post';

    protected $description = 'Test posting data to Google Sheets';

    public function handle(): int
    {
        $this->info('Testing Google Sheets posting...');

        try {
            $googleSheetsService = app(GoogleSheetsService::class);

            // Test data
            $testData = [
                'mission_id' => 999,
                'title' => 'Test School - Test Mission Recap',
                'content' => 'This is a test post to verify Google Sheets integration.',
                'media_url' => 'https://example.com/test-video.mp4',
                'school_name' => 'Test School',
                'mission_type' => 'Test Mission',
                'scheduled_for' => now()->addDays(3)->format('Y-m-d H:i:s'),
            ];

            $result = $googleSheetsService->addSocialMediaPost($testData);

            if ($result) {
                $this->info('✅ Test data posted successfully to Google Sheets!');
                $this->line('Check your Google Sheet to see the new row.');
                $this->line('This should trigger your Zapier workflow if configured.');

                return 0;
            } else {
                $this->error('❌ Failed to post test data');

                return 1;
            }

        } catch (Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return 1;
        }
    }
}
