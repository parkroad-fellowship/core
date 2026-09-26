<?php

namespace App\Filament\Resources\FinancialAccounts\Pages;

use App\Enums\PRFFinancialReportType;
use App\Filament\Resources\FinancialAccounts\FinancialAccountResource;
use App\Jobs\FinancialReport\CreateJob as CreateFinancialReportJob;
use App\Models\FinancialAccount;
use App\Models\FinancialReport;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewFinancialAccount extends ViewRecord
{
    protected static string $resource = FinancialAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('download_cashbook')
                ->label('Download cashbook')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function (): void {
                    CreateFinancialReportJob::dispatchSync([
                        'type' => PRFFinancialReportType::CASHBOOK->value,
                        'period_start' => now()->startOfYear()->toDateString(),
                        'period_end' => now()->toDateString(),
                        'requested_by' => Auth::id(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Cashbook generating')
                        ->body(
                            'You’ll get a notification (the bell, top right) with a download link when it’s ready. It will also be under Financial Reports.',
                        )
                        ->send();
                })
                ->visible(fn(): bool => userCan(FinancialReport::permission('create')))
                ->tooltip('Generate the full cashbook workbook for this year'),

            EditAction::make()->visible(fn(): bool => userCan(FinancialAccount::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(FinancialAccount::permission('view'));
    }
}
