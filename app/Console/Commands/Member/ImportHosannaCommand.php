<?php

namespace App\Console\Commands\Member;

use App\Imports\Member\HosannaImport;
use Exception;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportHosannaCommand extends Command
{
    protected $signature = 'app:import-hosanna {file : Path to the Hosanna Mission Team Excel file}';

    protected $description = 'Import members from the Hosanna Mission Team registration form';

    public function handle()
    {
        $this->info('Importing Hosanna Mission Team members...');

        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return Command::FAILURE;
        }

        try {
            $import = new HosannaImport;
            Excel::import($import, $filePath);

            $this->info($import->getSummary());

            if ($import->getSkippedCount() > 0) {
                $this->warn("{$import->getSkippedCount()} rows were skipped.");
                foreach ($import->getErrors() as $error) {
                    $this->line("  - {$error}");
                }
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $this->error('Import failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
