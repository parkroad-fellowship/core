<?php

namespace App\Console\Commands\Reporting;

use App\Enums\PRFMissionStatus;
use App\Models\Mission;
use App\Notifications\Mission\ExecutiveSummariesReportNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Notification;
use Spatie\Browsershot\Browsershot;
use Spatie\TemporaryDirectory\TemporaryDirectory;

use function Spatie\LaravelPdf\Support\pdf;

class ExportExecutiveSummariesToPdf extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'missions:export-summaries
                            {--from= : Start date filter (Y-m-d)}
                            {--to= : End date filter (Y-m-d)}
                            {--status=* : Override default status filter (accepts multiple values)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export mission executive summaries to a PDF and email to mission desk';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Validate dates if provided
        $from = null;
        $to = null;

        if ($this->option('from')) {
            try {
                $from = Carbon::parse($this->option('from'))->startOfDay();
            } catch (\Exception $e) {
                $this->error('Invalid --from date. Use Y-m-d format (e.g., 2026-01-28)');

                return self::FAILURE;
            }
        }

        if ($this->option('to')) {
            try {
                $to = Carbon::parse($this->option('to'))->endOfDay();
            } catch (\Exception $e) {
                $this->error('Invalid --to date. Use Y-m-d format (e.g., 2026-01-28)');

                return self::FAILURE;
            }
        }

        // Validate status values if provided
        $statusValues = $this->option('status') ?? [];
        if (! empty($statusValues)) {
            foreach ($statusValues as $status) {
                if (! in_array((int) $status, PRFMissionStatus::getElements())) {
                    $this->error("Invalid status value: {$status}");
                    $this->line('Valid statuses are: '.implode(', ', PRFMissionStatus::getElements()));

                    return self::FAILURE;
                }
            }
            $statuses = array_map('intval', $statusValues);
        } else {
            // Default statuses: SERVICED (5), CANCELLED (4), POSTPONED (7)
            $statuses = [
                PRFMissionStatus::SERVICED->value,
                PRFMissionStatus::CANCELLED->value,
                PRFMissionStatus::POSTPONED->value,
            ];
        }

        // Check config
        $emails = config('prf.app.missions_desk.emails');
        if (empty($emails)) {
            $this->error('Mission desk emails not configured in config/prf/app.php');

            return self::FAILURE;
        }

        // Build query
        $query = Mission::query()
            ->whereNotNull('executive_summary')
            ->whereIn('status', $statuses);

        // Apply date filters
        if ($from) {
            $query->where('start_date', '>=', $from);
        }
        if ($to) {
            $query->where('start_date', '<=', $to);
        }

        // Eager load relationships
        $missions = $query
            ->with(['school', 'missionType', 'schoolTerm', 'missionSubscriptions', 'souls'])
            ->orderBy('start_date', 'asc')
            ->get();

        // Check if we have results
        if ($missions->isEmpty()) {
            $this->warn('No missions with executive summaries found matching the criteria.');

            return self::SUCCESS;
        }

        // Build date range string for display
        $dateRange = null;
        if ($from && $to) {
            $dateRange = $from->format('M d, Y').' - '.$to->format('M d, Y');
        } elseif ($from) {
            $dateRange = 'From '.$from->format('M d, Y');
        } elseif ($to) {
            $dateRange = 'Until '.$to->format('M d, Y');
        }

        // Create temporary directory for PDF generation
        $temporaryDirectory = TemporaryDirectory::make()
            ->name('executive-summaries-'.now()->format('Y-m-d-His'))
            ->create();

        $fileName = 'mission-executive-summaries.pdf';
        $localPath = $temporaryDirectory->path($fileName);

        try {
            $this->line('Generating PDF with '.$missions->count().' missions...');

            // Generate PDF
            $pdf = pdf()
                ->withBrowsershot(function (Browsershot $browsershot) {
                    $browsershot
                        ->noSandbox()
                        ->ignoreHttpsErrors()
                        ->newHeadless()
                        ->format('A4')
                        ->addChromiumArguments(config('prf.app.reports.environment.chromium_args'))
                        ->setChromePath(config('prf.app.reports.environment.chrome_path'))
                        ->setNodeBinary(config('prf.app.reports.environment.node_path'))
                        ->setNpmBinary(config('prf.app.reports.environment.npm_path'))
                        ->timeout(120);
                })
                ->view('prf.reports.mission-executive-summaries', [
                    'missions' => $missions,
                    'dateRange' => $dateRange,
                ])
                ->name(downloadName: $fileName);

            // Save to local filesystem
            $pdf->save($localPath);

            $this->line('PDF generated successfully');

            // Send notification
            $this->line('Sending email to mission desk...');

            try {
                $notifiable = new class
                {
                    use Notifiable;

                    public function getKey()
                    {
                        return 'mission-desk';
                    }

                    public function routeNotificationForMail()
                    {
                        return config('prf.app.missions_desk.emails');
                    }
                };

                Notification::sendNow($notifiable, new ExecutiveSummariesReportNotification(
                    filePath: $localPath,
                    missionCount: $missions->count(),
                    dateRange: $dateRange,
                ));

                $this->info('✓ Email sent successfully to mission desk');
            } catch (\Exception $e) {
                $this->error('Failed to send email: '.$e->getMessage());
                $temporaryDirectory->delete();

                return self::FAILURE;
            }

            // Cleanup temp directory
            $temporaryDirectory->delete();
            $this->line('Temporary files cleaned up');

            $this->info('✓ Export completed successfully');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to generate PDF: '.$e->getMessage());

            // Cleanup on failure
            $temporaryDirectory->delete();

            return self::FAILURE;
        }
    }
}
