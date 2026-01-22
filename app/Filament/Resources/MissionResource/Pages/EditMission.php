<?php

namespace App\Filament\Resources\MissionResource\Pages;

use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use App\Enums\PRFMissionStatus;
use App\Filament\Resources\MissionResource;
use App\Jobs\Mission\EmailFinancialReportJob;
use App\Jobs\Mission\GenerateExecutiveSummaryJob;
use App\Jobs\Mission\NotifySchoolOfMissionJob;
use App\Jobs\Mission\NotifyWhatsAppGroupJob;
use App\Jobs\Mission\RequestSchoolFeedbackJob;
use App\Jobs\Mission\UploadFilesToDriveJob;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditMission extends EditRecord
{
    protected static string $resource = MissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Quick Actions dropdown group
            ActionGroup::make([
                Action::make('notify_school')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->label('Notify School')
                    ->modalDescription('This will send a notification to the school about this mission.')
                    ->action(function () {
                        NotifySchoolOfMissionJob::dispatch($this->record);
                        Notification::make()
                            ->title('School Notified')
                            ->body('Notification has been queued for delivery.')
                            ->success()
                            ->send();
                    }),
                Action::make('request_feedback')
                    ->icon('heroicon-o-inbox-arrow-down')
                    ->requiresConfirmation()
                    ->label('Request Feedback')
                    ->modalDescription('This will send a feedback request to the school.')
                    ->action(function () {
                        RequestSchoolFeedbackJob::dispatch($this->record);
                        Notification::make()
                            ->title('Feedback Requested')
                            ->body('Feedback request has been queued for delivery.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => $this->record->status >= PRFMissionStatus::SERVICED->value),
                Action::make('whatsapp_notification')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->requiresConfirmation()
                    ->label('WhatsApp Notification')
                    ->modalDescription('This will notify members to join the WhatsApp group.')
                    ->action(function () {
                        NotifyWhatsAppGroupJob::dispatch($this->record);
                        Notification::make()
                            ->title('WhatsApp Notification Sent')
                            ->body('Members have been notified to join the group.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => $this->record->status >= PRFMissionStatus::APPROVED->value),
            ])
                ->label('📢 Notifications')
                ->icon('heroicon-o-bell')
                ->color('info')
                ->button(),

            // Reports dropdown group
            ActionGroup::make([
                Action::make('download_expense_report')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->label('Download Expense Report')
                    ->url(fn () => route('reports.mission-expenses.export', ['missionUlid' => $this->record->ulid]))
                    ->openUrlInNewTab(),
                Action::make('email_expense_report')
                    ->icon('heroicon-o-envelope')
                    ->requiresConfirmation()
                    ->label('Email Expense Report')
                    ->modalDescription('This will email the expense report to the finance team.')
                    ->action(function () {
                        EmailFinancialReportJob::dispatch($this->record);
                        Notification::make()
                            ->title('Report Queued')
                            ->body('Expense report will be emailed shortly.')
                            ->success()
                            ->send();
                    }),
                Action::make('download_mission_report')
                    ->icon('heroicon-o-document-arrow-down')
                    ->label('Download Mission Report')
                    ->url(fn () => route('reports.missions.export', ['missionUlid' => $this->record->ulid]))
                    ->openUrlInNewTab(),
            ])
                ->label('📊 Reports')
                ->icon('heroicon-o-document-chart-bar')
                ->color('gray')
                ->button(),

            // AI & Tools dropdown group
            ActionGroup::make([
                Action::make('generate_summary')
                    ->icon('heroicon-o-sparkles')
                    ->requiresConfirmation()
                    ->label('Generate Executive Summary')
                    ->modalDescription('AI will generate an executive summary based on mission data.')
                    ->action(function () {
                        GenerateExecutiveSummaryJob::dispatch($this->record);
                        Notification::make()
                            ->title('Generating Summary')
                            ->body('Executive summary generation has been queued.')
                            ->success()
                            ->send();
                    }),
                Action::make('upload_to_drive')
                    ->icon('heroicon-o-cloud-arrow-up')
                    ->requiresConfirmation()
                    ->label('Upload Media to Drive')
                    ->modalDescription('This will upload all mission photos to Google Drive.')
                    ->action(function () {
                        UploadFilesToDriveJob::dispatch($this->record->id);
                        Notification::make()
                            ->title('Upload Started')
                            ->body('Media files are being uploaded to Drive.')
                            ->success()
                            ->send();
                    }),
            ])
                ->label('🤖 AI & Tools')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('warning')
                ->button(),

            // Standard actions
            ViewAction::make()
                ->visible(fn () => userCan('view mission')),
            DeleteAction::make()
                ->visible(fn () => userCan('delete mission')),
            ForceDeleteAction::make()
                ->visible(fn () => userCan('forceDelete mission')),
            RestoreAction::make()
                ->visible(fn () => userCan('restore mission')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('edit mission');
    }
}
