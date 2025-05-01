<?php

namespace App\Console\Commands\Member;

use App\Exports\Member\GmailExport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class GmailExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:gmail-export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export the users to a CSV file for Gmail import.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Exporting users to a CSV file for Gmail import...');

        Excel::store(new GmailExport, ('Console/Commands/Member/Gmail_Export.csv'), 'local');

        $this->info('Users exported successfully.');
    }
}
