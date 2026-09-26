<?php

namespace App\Filament\Actions;

use App\Enums\PRFMissionStatus;
use App\Jobs\Mission\CompleteJob;
use App\Models\Mission;
use App\Models\User;
use App\Services\MissionCompletionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class CompleteMissionAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'complete_mission';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Complete mission')
            ->icon('heroicon-o-check-badge')
            ->color(Color::Green)
            ->modalHeading('Complete this mission')
            ->modalDescription(
                fn(Mission $record): string => app(MissionCompletionService::class)->getCompletionChecklist(
                    $record,
                )['can_complete']
                        ? 'Everything needed is in place. Completing sends thank-yous, asks the school for feedback and emails the expense report.'
                        : 'A few things are still missing. Close this, add them from the tabs on the mission page, then come back.',
            )
            ->modalSubmitActionLabel('Complete mission')
            ->modalSubmitAction(fn(
                Action $action,
                Mission $record,
            ): Action|false => app(MissionCompletionService::class)->getCompletionChecklist($record)['can_complete']
                ? $action
                : false)
            ->modalCancelActionLabel('Close')
            ->modalIcon('heroicon-o-clipboard-document-check')
            ->modalIconColor(Color::Green)
            ->visible(
                fn(Mission $record): bool => (
                    $record->status->canMoveTo(PRFMissionStatus::SERVICED) && userCan(Mission::permission('edit'))
                ),
            )
            ->schema(function (Mission $record): array {
                $service = app(MissionCompletionService::class);
                $checklist = $service->getCompletionChecklist($record);

                return [
                    Placeholder::make('checklist')
                        ->label('')
                        ->content(function () use ($checklist): HtmlString {
                            return $this->buildChecklistHtml($checklist);
                        }),

                    Placeholder::make('status_message')
                        ->label('')
                        ->content(function () use ($checklist): HtmlString {
                            $canComplete = $checklist['can_complete'];
                            $message = $checklist['message'];

                            $bgColor = $canComplete
                                ? 'bg-green-50 dark:bg-green-900/20'
                                : 'bg-yellow-50 dark:bg-yellow-900/20';
                            $borderColor = $canComplete
                                ? 'border-green-200 dark:border-green-800'
                                : 'border-yellow-200 dark:border-yellow-800';
                            $textColor = $canComplete
                                ? 'text-green-800 dark:text-green-200'
                                : 'text-yellow-800 dark:text-yellow-200';
                            $icon = $canComplete
                                ? '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>'
                                : '<svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';

                            return new HtmlString("
                                <div class='{$bgColor} {$borderColor} {$textColor} border rounded-lg p-4 flex items-center gap-3'>
                                    {$icon}
                                    <span class='font-medium'>{$message}</span>
                                </div>
                            ");
                        }),
                ];
            })
            ->action(function (Mission $record): void {
                $actor = Auth::user();
                abort_unless($actor instanceof User, 403);

                CompleteJob::dispatchSync($record, $actor);

                Notification::make()
                    ->title('Mission completed')
                    ->body('Thank-you messages, the school feedback request and the expense report are on their way.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @param  array{can_complete: bool, checks: array<string, array{passed: bool, required: bool, label: string, description: string, count: int|null}>, message: string|null}  $checklist
     */
    protected function buildChecklistHtml(array $checklist): HtmlString
    {
        $html = '<div class="space-y-3">';

        foreach ($checklist['checks'] as $check) {
            $passed = $check['passed'];
            $icon = $passed
                ? '<svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'
                : '<svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';

            $bgColor = $passed ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20';
            $borderColor = $passed ? 'border-green-200 dark:border-green-800' : 'border-red-200 dark:border-red-800';

            $html .=
                "
                <div class='{$bgColor} {$borderColor} border rounded-lg p-3 flex items-start gap-3'>
                    {$icon}
                    <div>
                        <div class='font-medium text-gray-900 dark:text-gray-100'>"
                . e($check['label'])
                . "</div>
                        <div class='text-sm text-gray-600 dark:text-gray-400'>"
                . e($check['description'])
                . '</div>
                    </div>
                </div>
            ';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }
}
