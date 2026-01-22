<?php

namespace App\Console\Commands\Member;

use App\Imports\Member\UploadImport;
use Exception;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-members {file : Path to the Excel file to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import members from an excel sheet document';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Importing members...');

        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        try {
            Excel::import(new UploadImport, $filePath);
            $this->info('Members imported successfully.');

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Import failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
