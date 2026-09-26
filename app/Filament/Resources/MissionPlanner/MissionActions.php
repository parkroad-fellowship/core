<?php

namespace App\Filament\Resources\MissionPlanner;

use App\Enums\PRFMissionStatus;
use App\Filament\Actions\CompleteMissionAction;
use App\Filament\Forms\Schemas\MediaSchema;
use App\Jobs\AccountingEvent\EmailFinancialReportJob;
use App\Jobs\AccountingEvent\MakeZeroRequisitionJob;
use App\Jobs\Mission\ApproveJob;
use App\Jobs\Mission\CancelJob;
use App\Jobs\Mission\GenerateExecutiveSummaryJob;
use App\Jobs\Mission\NotifySchoolOfMissionJob;
use App\Jobs\Mission\NotifyWhatsAppGroupJob;
use App\Jobs\Mission\PostponeJob;
use App\Jobs\Mission\RejectJob;
use App\Jobs\Mission\RequestSchoolFeedbackJob;
use App\Jobs\Mission\UpdateJob;
use App\Jobs\Mission\UploadFilesToDriveJob;
use App\Models\Mission;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

/**
 * The buttons that move a mission along (approve, reject, postpone, cancel, complete) and the
 * "More" menu, shared by the classic mission pages, the guided view and the list.
 */
class MissionActions
{
    /**
     * @return list<Action|ActionGroup>
     */
    public static function header(): array
    {
        return [
            self::approve(),
            CompleteMissionAction::make(),
            self::postpone(),
            self::reject(),
            self::cancel(),
            self::more(),
        ];
    }

    public static function approve(): Action
    {
        return Action::make('approve')
            ->label(fn(Mission $record): string => $record->status->is(PRFMissionStatus::POSTPONED)
                ? 'Approve again'
                : 'Approve')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading(
                fn(Mission $record): string => (
                    'Approve the mission to ' . ($record->school->name ?? 'this school') . '?'
                ),
            )
            ->modalDescription(
                'The school gets an SMS, members are told and can volunteer in the app, and the money record is opened.',
            )
            ->modalSubmitActionLabel('Approve')
            ->action(function (Mission $record): void {
                ApproveJob::dispatchSync($record, self::actor());

                Notification::make()->success()->title('Mission approved')->send();
            })
            ->visible(fn(Mission $record): bool => self::can($record, PRFMissionStatus::APPROVED));
    }

    public static function reject(): Action
    {
        return self::withReason(Action::make('reject')
            ->label('Reject')
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->modalHeading('Reject this mission?')
            ->modalDescription('Nobody is notified. The reason is kept on the mission.')
            ->modalSubmitActionLabel('Reject')
            ->action(function (Mission $record, array $data): void {
                RejectJob::dispatchSync($record, self::actor(), ['reason' => $data['reason']]);

                Notification::make()->success()->title('Mission rejected')->send();
            })
            ->visible(fn(Mission $record): bool => self::can($record, PRFMissionStatus::REJECTED)));
    }

    public static function cancel(): Action
    {
        return self::withReason(Action::make('cancel')
            ->label('Cancel mission')
            ->icon('heroicon-m-no-symbol')
            ->color('danger')
            ->modalHeading('Cancel this mission?')
            ->modalDescription('Members are told it’s cancelled, with the reason below.')
            ->modalSubmitActionLabel('Cancel the mission')
            ->modalCancelActionLabel('Keep it')
            ->action(function (Mission $record, array $data): void {
                CancelJob::dispatchSync($record, self::actor(), ['reason' => $data['reason']]);

                Notification::make()->success()->title('Mission cancelled')->send();
            })
            ->visible(fn(Mission $record): bool => self::can($record, PRFMissionStatus::CANCELLED)));
    }

    public static function postpone(): Action
    {
        return Action::make('postpone')
            ->label('Postpone')
            ->icon('heroicon-m-calendar-days')
            ->color('warning')
            ->modalHeading('Postpone this mission')
            ->modalDescription(
                'Members are told it has moved, with the reason. Leave the dates as they are if the new date isn’t known yet.',
            )
            ->modalSubmitActionLabel('Postpone')
            ->fillForm(fn(Mission $record): array => [
                'start_date' => $record->start_date,
                'end_date' => $record->end_date,
                'start_time' => $record->start_time,
                'end_time' => $record->end_time,
            ])
            ->schema([
                Textarea::make('reason')
                    ->label('Why is it being postponed?')
                    ->placeholder('e.g. The school has exams that week')
                    ->required()
                    ->maxLength(1000)
                    ->rows(3),
                Grid::make(2)->schema([
                    DatePicker::make('start_date')->label('New start date')->native(false)->required(),
                    DatePicker::make('end_date')
                        ->label('New end date')
                        ->native(false)
                        ->required()
                        ->afterOrEqual('start_date'),
                    TimePicker::make('start_time')->label('Start time')->seconds(false),
                    TimePicker::make('end_time')->label('End time')->seconds(false),
                ]),
            ])
            ->action(function (Mission $record, array $data): void {
                PostponeJob::dispatchSync(
                    $record,
                    self::actor(),
                    array_filter($data, is_string(...), ARRAY_FILTER_USE_KEY),
                );

                Notification::make()->success()->title('Mission postponed')->send();
            })
            ->visible(fn(Mission $record): bool => self::can($record, PRFMissionStatus::POSTPONED));
    }

    public static function whatsappLink(): Action
    {
        return Action::make('whatsappLink')
            ->label('WhatsApp group link')
            ->icon('heroicon-m-link')
            ->modalHeading('WhatsApp group link')
            ->modalDescription('Approved volunteers are invited to the group automatically once the link is saved.')
            ->fillForm(fn(Mission $record): array => ['whats_app_link' => $record->whats_app_link])
            ->schema([
                TextInput::make('whats_app_link')
                    ->label('Group invite link')
                    ->placeholder('https://chat.whatsapp.com/…')
                    ->url()
                    ->rule('regex:#^https://chat\.whatsapp\.com/#i')
                    ->validationMessages([
                        'regex' => 'Paste the group’s invite link, starting with https://chat.whatsapp.com/',
                    ])
                    ->required(),
            ])
            ->modalSubmitActionLabel('Save link')
            ->action(function (Mission $record, array $data): void {
                UpdateJob::dispatchSync(['whats_app_link' => $data['whats_app_link']], $record->ulid);

                Notification::make()
                    ->success()
                    ->title('WhatsApp link saved')
                    ->body('Volunteers are being invited.')
                    ->send();
            })
            ->visible(fn(): bool => userCan(Mission::permission('edit')));
    }

    public static function uploadPhotos(): Action
    {
        return Action::make('uploadPhotos')
            ->label('Upload photos')
            ->icon('heroicon-m-photo')
            ->modalHeading('Mission photos')
            ->modalDescription('Add a few good photos. They go into the mission report.')
            ->schema([
                MediaSchema::uploadField(
                    collection: Mission::MISSION_PHOTOS,
                    label: 'Photos',
                    multiple: true,
                    maxFiles: 20,
                    acceptedFileTypes: ['image/jpeg', 'image/png', 'image/webp'],
                    helperText: 'JPG, PNG or WebP. Up to 20 photos.',
                ),
            ])
            ->modalSubmitActionLabel('Save photos')
            ->action(function (Schema $schema): void {
                $schema->saveRelationships();

                Notification::make()->success()->title('Photos saved')->send();
            })
            ->visible(fn(): bool => userCan(Mission::permission('edit')));
    }

    public static function requestFeedback(): Action
    {
        return Action::make('requestFeedback')
            ->label('Ask the school for feedback')
            ->icon('heroicon-m-inbox-arrow-down')
            ->requiresConfirmation()
            ->modalDescription('The school contacts get an SMS asking how the mission went.')
            ->action(function (Mission $record): void {
                RequestSchoolFeedbackJob::dispatch($record);

                Notification::make()->success()->title('Feedback request sent')->send();
            })
            ->visible(
                fn(Mission $record): bool => (
                    $record->status->is(PRFMissionStatus::SERVICED) && userCan(Mission::permission('edit'))
                ),
            );
    }

    /**
     * Everything that isn't a status change, in one menu.
     */
    public static function more(): ActionGroup
    {
        return ActionGroup::make([
            ActionGroup::make([
                self::whatsappLink(),
                self::uploadPhotos(),
            ])->dropdown(false),

            ActionGroup::make([
                Action::make('notifySchool')
                    ->label('Send the school an SMS about this mission')
                    ->icon('heroicon-m-paper-airplane')
                    ->requiresConfirmation()
                    ->action(function (Mission $record): void {
                        NotifySchoolOfMissionJob::dispatch($record);
                        Notification::make()->success()->title('SMS to the school is on its way')->send();
                    })
                    ->visible(fn(Mission $record): bool => $record->status->is(...[
                        PRFMissionStatus::APPROVED,
                        PRFMissionStatus::FULLY_SUBSCRIBED,
                    ])),
                Action::make('notifyWhatsApp')
                    ->label('Remind volunteers to join WhatsApp')
                    ->icon('heroicon-m-chat-bubble-left-ellipsis')
                    ->requiresConfirmation()
                    ->action(function (Mission $record): void {
                        NotifyWhatsAppGroupJob::dispatch($record);
                        Notification::make()->success()->title('Reminder sent')->send();
                    })
                    ->visible(
                        fn(Mission $record): bool => (
                            filled($record->whats_app_link)
                            && $record->status->is(...[PRFMissionStatus::APPROVED, PRFMissionStatus::FULLY_SUBSCRIBED])
                        ),
                    ),
                self::requestFeedback(),
            ])->dropdown(false),

            ActionGroup::make([
                Action::make('downloadMissionReport')
                    ->label('Download mission report')
                    ->icon('heroicon-m-document-arrow-down')
                    ->url(fn(Mission $record): string => URL::temporarySignedRoute(
                        'reports.missions.export',
                        now()->addMinutes(30),
                        ['missionUlid' => $record->ulid],
                    ))
                    ->openUrlInNewTab(),
                Action::make('downloadExpenseReport')
                    ->label('Download expense report')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->url(fn(Mission $record): string => URL::temporarySignedRoute(
                        'reports.mission-expenses.export',
                        now()->addMinutes(30),
                        ['missionUlid' => $record->ulid],
                    ))
                    ->openUrlInNewTab()
                    ->visible(fn(Mission $record): bool => $record->accountingEvent !== null),
                Action::make('emailExpenseReport')
                    ->label('Email expense report to finance')
                    ->icon('heroicon-m-envelope')
                    ->requiresConfirmation()
                    ->action(function (Mission $record): void {
                        EmailFinancialReportJob::dispatch((string) $record->accountingEvent?->ulid);
                        Notification::make()->success()->title('Expense report is on its way')->send();
                    })
                    ->visible(fn(Mission $record): bool => $record->accountingEvent !== null),
                Action::make('zeroRequisition')
                    ->label('Members pay their own way (zero requisition)')
                    ->icon('heroicon-m-calculator')
                    ->requiresConfirmation()
                    ->modalDescription(
                        'Use this when the fellowship isn’t funding the mission. It records a requisition of KES 0 so the money step is complete.',
                    )
                    ->action(function (Mission $record): void {
                        if ($record->accountingEvent !== null) {
                            MakeZeroRequisitionJob::dispatch($record->accountingEvent);
                        }

                        Notification::make()->success()->title('Zero requisition recorded')->send();
                    })
                    ->visible(
                        fn(Mission $record): bool => (
                            $record->accountingEvent !== null
                            && $record->accountingEvent->requisitions()->doesntExist()
                        ),
                    ),
            ])->dropdown(false),

            ActionGroup::make([
                Action::make('generateSummary')
                    ->label('Write the summary with AI')
                    ->icon('heroicon-m-sparkles')
                    ->requiresConfirmation()
                    ->modalDescription(
                        'Drafts the executive summary from the debrief, souls and sessions. You can edit it afterwards.',
                    )
                    ->action(function (Mission $record): void {
                        GenerateExecutiveSummaryJob::dispatch($record);
                        Notification::make()
                            ->success()
                            ->title('Summary is being written')
                            ->body('It appears in a minute or two.')
                            ->send();
                    }),
                Action::make('uploadToDrive')
                    ->label('Copy photos to Google Drive')
                    ->icon('heroicon-m-cloud-arrow-up')
                    ->requiresConfirmation()
                    ->action(function (Mission $record): void {
                        UploadFilesToDriveJob::dispatch($record->id);
                        Notification::make()->success()->title('Copying photos to Drive')->send();
                    }),
            ])->dropdown(false),
        ])
            ->label('More')
            ->icon('heroicon-m-ellipsis-horizontal')
            ->color('gray')
            ->button()
            ->visible(fn(): bool => userCan(Mission::permission('edit')));
    }

    public static function guidedView(): Action
    {
        return Action::make('guidedView')
            ->label('Guided view')
            ->icon('heroicon-m-map')
            ->color('gray')
            ->link()
            ->url(fn(Mission $record): string => MissionPlannerResource::getUrl('view', ['record' => $record]));
    }

    public static function classicView(): Action
    {
        return Action::make('classicView')
            ->label('Classic view')
            ->icon('heroicon-m-rectangle-stack')
            ->color('gray')
            ->link()
            ->url(fn(Mission $record): string => \App\Filament\Resources\Missions\MissionResource::getUrl('view', [
                'record' => $record,
            ]));
    }

    private static function withReason(Action $action): Action
    {
        return $action->schema([
            Textarea::make('reason')
                ->label('Reason')
                ->placeholder('A short note everyone will understand')
                ->required()
                ->maxLength(1000)
                ->rows(3),
        ]);
    }

    private static function can(Mission $record, PRFMissionStatus $to): bool
    {
        return $record->status->canMoveTo($to) && userCan(Mission::permission('edit'));
    }

    private static function actor(): User
    {
        $user = Auth::user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
