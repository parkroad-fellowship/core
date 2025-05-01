<?php

namespace App\Console\Commands\Member;

use App\Imports\Member\UploadImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-members';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import members from the excel sheet document';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Importing members...');

        Excel::import(new UploadImport, app_path('Console/Commands/Member/Member_Contacts.xlsx'));

        $this->info('Members imported successfully.');
    }
}
