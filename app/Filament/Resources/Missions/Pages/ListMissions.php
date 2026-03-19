<?php

namespace App\Filament\Resources\Missions\Pages;

use App\Enums\PRFMissionStatus;
use App\Filament\Resources\Missions\MissionResource;
use App\Helpers\Utils;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

use function Spatie\LaravelPdf\Support\pdf;

class ListMissions extends ListRecords
{
    protected static string $resource = MissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create mission')),
            Action::make('export_schedule')
                ->label('Export Schedule')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    return $this->exportSchedulePdf();
                })
                ->visible(fn () => userCan('view mission')),
        ];
    }

    public function exportSchedulePdf()
    {
        $query = $this->getFilteredSortedTableQuery();

        $missions = $query
            ->whereIn('status', [
                PRFMissionStatus::APPROVED->value,
                PRFMissionStatus::FULLY_SUBSCRIBED->value,
            ])
            ->with([
                'school',
                'missionType',
                'schoolTerm',
                'missionSubscriptions.member',
                'offlineMembers',
            ])
            ->get();

        if ($missions->isEmpty()) {
            $this->sendNotification('warning', 'No Missions', 'There are no missions to export with the current filters.');

            return null;
        }

        $termName = $missions->first()?->schoolTerm?->name;
        $uniqueTerms = $missions->pluck('schoolTerm.name')->unique()->filter();

        $title = $uniqueTerms->count() === 1
            ? "{$uniqueTerms->first()} Missions Schedule"
            : 'Missions Schedule';

        $subtitle = $uniqueTerms->count() === 1
            ? "Schedule for {$uniqueTerms->first()}"
            : 'Filtered Missions List ('.$uniqueTerms->count().' terms)';

        $filename = Utils::generateMissionsScheduleFileName(
            termName: $uniqueTerms->count() === 1 ? $termName : null,
        );

        $tempPath = tempnam(sys_get_temp_dir(), 'pdf_');

        pdf()
            ->view('prf.reports.missions-schedule-pdf', [
                'missions' => $missions,
                'title' => $title,
                'subtitle' => $subtitle,
            ])
            ->save($tempPath);

        return response()->streamDownload(function () use ($tempPath) {
            echo file_get_contents($tempPath);
            @unlink($tempPath);
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    protected function sendNotification(string $type, string $title, string $body): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->{$type}()
            ->send();
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny mission');
    }
}
