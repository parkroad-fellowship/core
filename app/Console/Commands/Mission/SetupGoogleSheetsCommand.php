<?php

namespace App\Console\Commands\Mission;

use App\Services\GoogleSheetsService;
use Exception;
use Illuminate\Console\Command;

class SetupGoogleSheetsCommand extends Command
{
    protected $signature = 'mission:setup-google-sheets
                          {--test : Test the connection to Google Sheets}
                          {--create-headers : Create headers in the Google Sheet}';

    protected $description = 'Set up Google Sheets integration for social media posting';

    public function handle(): int
    {
        $this->info('Setting up Google Sheets integration...');

        try {
            $googleSheetsService = app(GoogleSheetsService::class);

            if ($this->option('test')) {
                return $this->testConnection($googleSheetsService);
            }

            if ($this->option('create-headers')) {
                return $this->createHeaders($googleSheetsService);
            }

            // Default: show configuration info
            $this->showConfiguration();

            return 0;

        } catch (Exception $e) {
            $this->error('Failed to initialize Google Sheets service: '.$e->getMessage());

            return 1;
        }
    }

    private function testConnection(GoogleSheetsService $service): int
    {
        $this->info('Testing connection to Google Sheets...');

        $result = $service->testConnection();

        if ($result['success']) {
            $this->info('✅ Connection successful!');
            $this->line("Spreadsheet title: {$result['title']}");
            $this->line("Number of sheets: {$result['sheet_count']}");

            return 0;
        } else {
            $this->error('❌ Connection failed: '.$result['error']);

            return 1;
        }
    }

    private function createHeaders(GoogleSheetsService $service): int
    {
        $this->info('Creating headers in Google Sheets...');

        try {
            $service->createHeaders();
            $this->info('✅ Headers created successfully!');

            return 0;
        } catch (Exception $e) {
            $this->error('❌ Failed to create headers: '.$e->getMessage());

            return 1;
        }
    }

    private function showConfiguration(): void
    {
        $this->info('Google Sheets Configuration:');
        $this->line('');

        $spreadsheetId = config('prf.hooks.google_sheets.spreadsheet_id');
        $sheetName = config('prf.hooks.google_sheets.sheet_name');
        $keyPath = config('prf.hooks.google_sheets.service_account_key_path');

        $this->line('Spreadsheet ID: '.($spreadsheetId ?: '❌ Not configured'));
        $this->line('Sheet Name: '.($sheetName ?: '❌ Not configured'));
        $this->line('Service Account Key: '.($keyPath ?: '❌ Not configured'));

        $this->line('');
        $this->info('Environment Variables Required:');
        $this->line('GOOGLE_SERVICE_ACCOUNT_KEY_PATH=/path/to/service-account-key.json');
        $this->line('GOOGLE_SHEETS_SOCIAL_MEDIA_SPREADSHEET_ID=your_spreadsheet_id');
        $this->line('GOOGLE_SHEETS_SOCIAL_MEDIA_SHEET_NAME="Social Media Posts"');

        $this->line('');
        $this->info('Commands:');
        $this->line('php artisan mission:setup-google-sheets --test');
        $this->line('php artisan mission:setup-google-sheets --create-headers');
    }
}
