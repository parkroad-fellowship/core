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
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Excel::store(new GmailExport, app_path('Console/Commands/Member/Gmail_Export.csv'), 'local');
    }
}
