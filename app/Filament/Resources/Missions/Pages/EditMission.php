<?php

namespace App\Filament\Resources\Missions\Pages;

use App\Enums\PRFMissionStatus;
use App\Filament\Actions\CompleteMissionAction;
use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\MissionPlanner\MissionPlannerResource;
use App\Filament\Resources\Missions\MissionResource;
use App\Jobs\Mission\ApproveJob;
use App\Jobs\Mission\CancelJob;
use App\Jobs\Mission\MarkFullySubscribedJob;
use App\Jobs\Mission\PostponeJob;
use App\Jobs\Mission\RejectJob;
use App\Models\Mission;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditMission extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = MissionResource::class;

    /**
     * A status change goes through its action job, so the rules, the reason and the notifications
     * are the same as on the guided screen. Everything else saves as before.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof Mission);

        $newStatus = is_numeric($data['status'] ?? null) ? PRFMissionStatus::from((int) $data['status']) : null;
        $reason = ['reason' => $data['status_reason'] ?? null];
        $details = array_filter(Arr::except($data, ['status', 'status_reason']), is_string(...), ARRAY_FILTER_USE_KEY);

        // Postponing moves the dates in the same step, so members are told the original dates.
        if ($newStatus === PRFMissionStatus::POSTPONED && !$record->status->is($newStatus)) {
            $dates = ['start_date', 'end_date', 'start_time', 'end_time'];
            $reason = [...$reason, ...array_filter(Arr::only($details, $dates), is_string(...), ARRAY_FILTER_USE_KEY)];
            $details = array_filter(Arr::except($details, $dates), is_string(...), ARRAY_FILTER_USE_KEY);
        }

        return DB::transaction(function () use ($record, $newStatus, $reason, $details): Mission {
            $mission = parent::handleRecordUpdate($record, $details);
            assert($mission instanceof Mission);

            if ($newStatus === null || $mission->status->is($newStatus)) {
                return $mission;
            }

            $actor = Auth::user();
            abort_unless($actor instanceof User, 403);

            match ($newStatus) {
                PRFMissionStatus::APPROVED => ApproveJob::dispatchSync($mission, $actor),
                PRFMissionStatus::FULLY_SUBSCRIBED => MarkFullySubscribedJob::dispatchSync($mission, $actor),
                PRFMissionStatus::REJECTED => RejectJob::dispatchSync($mission, $actor, $reason),
                PRFMissionStatus::CANCELLED => CancelJob::dispatchSync($mission, $actor, $reason),
                PRFMissionStatus::POSTPONED => PostponeJob::dispatchSync($mission, $actor, $reason),
                default => throw ValidationException::withMessages([
                    'data.status' => 'Use the Complete Mission button to mark a mission as serviced.',
                ]),
            };

            return $mission->refresh();
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('guidedView')
                ->label('Guided view')
                ->icon('heroicon-m-map')
                ->color('gray')
                ->link()
                ->url(fn(Mission $record): string => MissionPlannerResource::getUrl('view', ['record' => $record])),
            CompleteMissionAction::make(),
            MissionResource::getNotificationActions(),
            MissionResource::getReportActions(),
            MissionResource::getAIToolsActions(),
            ViewAction::make()->visible(fn() => userCan(Mission::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Mission::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Mission::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Mission::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Mission::permission('edit'));
    }
}
