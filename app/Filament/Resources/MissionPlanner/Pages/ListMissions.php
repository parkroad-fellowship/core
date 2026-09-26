<?php

namespace App\Filament\Resources\MissionPlanner\Pages;

use App\Enums\PRFMissionStatus;
use App\Filament\Resources\MissionPlanner\MissionPlannerResource;
use App\Helpers\Utils;
use App\Models\Mission;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Spatie\LaravelPdf\Support\pdf;

class ListMissions extends ListRecords
{
    protected static string $resource = MissionPlannerResource::class;

    public function getSubheading(): ?string
    {
        return 'Every mission, newest first. Open one to follow it step by step.';
    }

    public function getTabs(): array
    {
        $count = fn(array $statuses): int => Mission::query()->whereIn('status', $statuses)->count();
        $active = [PRFMissionStatus::APPROVED, PRFMissionStatus::FULLY_SUBSCRIBED];

        return [
            'all' => Tab::make('All'),
            'pending' => Tab::make('Needs approval')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', PRFMissionStatus::PENDING))
                ->badge($count([PRFMissionStatus::PENDING]) ?: null)
                ->badgeColor('warning'),
            'upcoming' => Tab::make('Upcoming')
                ->icon('heroicon-m-calendar')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', $active)->whereDate(
                    'start_date',
                    '>=',
                    today(),
                ))
                ->badge(
                    Mission::query()->whereIn('status', $active)->whereDate('start_date', '>=', today())->count()
                    ?: null,
                ),
            'to_close' => Tab::make('To close')
                ->icon('heroicon-m-clipboard-document-check')
                ->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', $active)->whereDate(
                    'start_date',
                    '<',
                    today(),
                ))
                ->badge(
                    Mission::query()->whereIn('status', $active)->whereDate('start_date', '<', today())->count()
                    ?: null,
                )
                ->badgeColor('danger'),
            'postponed' => Tab::make('Postponed')->modifyQueryUsing(
                fn(Builder $query) => $query->where('status', PRFMissionStatus::POSTPONED),
            ),
            'done' => Tab::make('Done')
                ->icon('heroicon-m-check-badge')
                ->modifyQueryUsing(fn(Builder $query) => $query->where('status', PRFMissionStatus::SERVICED)),
            'stopped' => Tab::make(
                'Cancelled or rejected',
            )->modifyQueryUsing(fn(Builder $query) => $query->whereIn('status', [
                PRFMissionStatus::CANCELLED,
                PRFMissionStatus::REJECTED,
            ])),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New mission')
                ->visible(fn() => userCan(Mission::permission('create'))),
            Action::make('export_schedule')
                ->label('Download schedule (PDF)')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function () {
                    return $this->exportSchedulePdf();
                })
                ->visible(fn() => userCan(Mission::permission('view'))),
        ];
    }

    /**
     * A PDF of the upcoming approved missions in the current view, for sharing with the team.
     */
    public function exportSchedulePdf(): ?StreamedResponse
    {
        $filtered = $this->getFilteredSortedTableQuery();

        if ($filtered === null) {
            return null;
        }

        $missions = Mission::query()
            ->whereIn('id', $filtered->clone()->select('missions.id'))
            ->whereIn('status', [PRFMissionStatus::APPROVED, PRFMissionStatus::FULLY_SUBSCRIBED])
            ->upcoming()
            ->with(['school', 'missionType', 'schoolTerm', 'missionSubscriptions.member', 'offlineMembers'])
            ->orderBy('start_date')
            ->get();

        if ($missions->isEmpty()) {
            $this->sendNotification(
                'warning',
                'Nothing to download',
                'There are no upcoming approved missions in this view.',
            );

            return null;
        }

        $terms = $missions
            ->map(fn(Mission $mission): ?string => $mission->schoolTerm?->name)
            ->filter()
            ->unique()
            ->values();
        $singleTerm = $terms->count() === 1 ? (string) $terms->first() : null;

        $title = $singleTerm !== null ? "{$singleTerm} Missions Schedule" : 'Missions Schedule';
        $subtitle = $singleTerm !== null ? "Schedule for {$singleTerm}" : "Filtered missions ({$terms->count()} terms)";
        $generatedName = Utils::generateMissionsScheduleFileName(termName: $singleTerm);
        $filename = is_string($generatedName) ? $generatedName : 'missions-schedule.pdf';

        $tempPath = (string) tempnam(sys_get_temp_dir(), 'pdf_');

        pdf()
            ->view('prf.reports.missions-schedule-pdf', [
                'missions' => $missions,
                'title' => $title,
                'subtitle' => $subtitle,
            ])
            ->save($tempPath);

        return response()->streamDownload(
            function () use ($tempPath): void {
                echo file_get_contents($tempPath);
                @unlink($tempPath);
            },
            $filename,
            ['Content-Type' => 'application/pdf'],
        );
    }

    protected function sendNotification(string $type, string $title, string $body): void
    {
        $notification = Notification::make()->title($title)->body($body);

        match ($type) {
            'warning' => $notification->warning(),
            'danger' => $notification->danger(),
            default => $notification->success(),
        };

        $notification->send();
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Mission::permission('viewAny'));
    }
}
