<?php

namespace App\Http\Controllers\Finance;

use App\Filament\Resources\FinancialReports\FinancialReportResource;
use App\Models\FinancialReport;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Downloads a generated report for a signed-in panel user. The in-app "report ready" notification
 * and the Financial Reports table link here, so a report is reachable even where email isn't set up.
 */
class DownloadFinancialReportController
{
    public function __invoke(string $ulid): StreamedResponse|RedirectResponse
    {
        abort_unless(userCan(FinancialReport::permission('view')), 403);

        $report = FinancialReport::query()->where('ulid', $ulid)->firstOrFail();

        if (!$report->isReady() || !$report->fileExists()) {
            Notification::make()
                ->warning()
                ->title('That file is no longer available')
                ->body('Regenerate the report from this list to get a fresh copy.')
                ->send();

            return redirect(FinancialReportResource::getUrl('index'));
        }

        return FinancialReport::disk()->download((string) $report->file_path, $report->downloadName());
    }
}
