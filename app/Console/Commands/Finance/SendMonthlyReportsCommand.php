<?php

namespace App\Console\Commands\Finance;

use App\Console\Concerns\RunsForEachTenant;
use App\Enums\PRFFinancialReportType;
use App\Jobs\FinancialReport\CreateJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendMonthlyReportsCommand extends Command
{
    use RunsForEachTenant;

    protected $signature = 'prf:finance:send-monthly-reports {--month= : Any date in the month to report on (defaults to last month)}';

    protected $description = "Generate last month's accountability workbook and impact summary and email them to the treasurer and chair";

    public function handle(): int
    {
        $month = $this->option('month')
            ? Carbon::parse((string) $this->option('month'))
            : Carbon::today()->subMonthNoOverflow();

        $this->forEachTenant(function () use ($month): void {
            foreach ([
                PRFFinancialReportType::MONTHLY_ACCOUNTABILITY,
                PRFFinancialReportType::IMPACT_SUMMARY,
            ] as $type) {
                CreateJob::dispatchSync([
                    'type' => $type->value,
                    'period_start' => $month->copy()->startOfMonth()->toDateString(),
                    'period_end' => $month->copy()->endOfMonth()->toDateString(),
                ]);
            }
        });

        $this->info("Queued {$month->format('F Y')} reports for every fellowship.");

        return self::SUCCESS;
    }
}
