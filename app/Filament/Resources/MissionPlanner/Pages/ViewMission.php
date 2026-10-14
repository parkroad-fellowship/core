<?php

namespace App\Filament\Resources\MissionPlanner\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\MissionPlanner\MissionActions;
use App\Filament\Resources\MissionPlanner\MissionPlannerResource;
use App\Models\Mission;
use App\Services\Missions\MissionProgress;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

/**
 * A step-by-step view of one mission: where it is in the journey, the one thing to do next, and
 * the checklist, above the usual tabs. The classic view stays at the usual URL for those who
 * prefer it.
 */
class ViewMission extends ViewRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = MissionPlannerResource::class;

    public function getTitle(): string|Htmlable
    {
        $mission = $this->getMission();

        return implode(' · ', array_filter([$mission->school?->name, $mission->missionType?->name]));
    }

    public function getSubheading(): ?string
    {
        $mission = $this->getMission();

        return (
            $mission->start_date->format('l j F Y') . ($mission->start_time !== null ? ", {$mission->start_time}" : '')
        );
    }

    public function getBreadcrumb(): string
    {
        return 'Guided view';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            View::make('filament.missions.guide')->viewData(fn(): array => [
                'progress' => new MissionProgress($this->getMission()),
                'mission' => $this->getMission(),
            ]),
            $this->getRelationManagersContentComponent(),
            Section::make('Details')
                ->description('School, dates, theme and preparation notes.')
                ->icon('heroicon-o-information-circle')
                ->collapsible()
                ->collapsed()
                ->schema([EmbeddedSchema::make('infolist')]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            ...MissionActions::header(),
            EditAction::make()->visible(fn() => userCan(Mission::permission('edit'))),
            MissionActions::classicView(),
        ];
    }

    /**
     * The label of the tab a checklist item or next step points to.
     */
    public function tabLabel(?string $tab): ?string
    {
        return match ($tab) {
            MissionProgress::TAB_TEAM => 'Team',
            MissionProgress::TAB_MONEY => 'Money',
            MissionProgress::TAB_DAY => 'Mission day',
            MissionProgress::TAB_OUTCOMES => 'Outcomes',
            default => null,
        };
    }

    /**
     * Header actions the guide's buttons can open, keyed by MissionProgress action names.
     */
    public function actionName(?string $action): ?string
    {
        return match ($action) {
            'complete' => 'complete_mission',
            null => null,
            default => $action,
        };
    }

    private function getMission(): Mission
    {
        $mission = $this->getRecord();
        assert($mission instanceof Mission);

        return $mission;
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Mission::permission('view'));
    }
}
