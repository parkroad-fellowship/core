<?php

namespace App\Filament\Resources\FinancialReports;

use App\Enums\PRFFinancialReportType;
use App\Enums\PRFProcessingStatus;
use App\Filament\Resources\FinancialReports\Pages\ListFinancialReports;
use App\Jobs\FinancialReport\GenerateJob;
use App\Models\FinancialReport;
use App\Notifications\FinancialReport\FinancialReportReadyNotification;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FinancialReportResource extends Resource
{
    protected static ?string $model = FinancialReport::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'Financial Report';

    protected static ?string $pluralModelLabel = 'Financial Reports';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationTooltip = 'Generated cashbooks, accountability workbooks and summaries';

    public static function table(Table $table): Table
    {
        return $table
            ->poll(fn(): ?string => FinancialReport::query()
                ->whereIn('status', [PRFProcessingStatus::PENDING->value, PRFProcessingStatus::PROCESSING->value])
                ->exists()
                    ? '5s'
                    : null)
            ->columns([
                TextColumn::make('type')
                    ->label('Report')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-document-chart-bar')
                    ->formatStateUsing(fn(FinancialReport $record): string => $record->type->getLabel())
                    ->sortable()
                    ->tooltip('What was generated'),

                TextColumn::make('period')
                    ->label('Period')
                    ->state(
                        fn(FinancialReport $record): string => (
                            $record->period_start->format('M j, Y') . ' → ' . $record->period_end->format('M j, Y')
                        ),
                    )
                    ->sortable(
                        query: fn(Builder $query, string $direction): Builder => $query->orderBy(
                            'period_start',
                            $direction,
                        ),
                    )
                    ->tooltip('The dates this report covers'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(FinancialReport $record): string => $record->status->getLabel())
                    ->color(fn(FinancialReport $record): string => $record->status->getColor())
                    ->sortable()
                    ->tooltip('Pending = queued, Processing = generating, Completed = ready to download'),

                TextColumn::make('requestedBy.name')
                    ->label('Requested By')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('Scheduled')
                    ->tooltip('Who asked for this report (scheduled reports have no requester)'),

                TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—')
                    ->tooltip('When generation finished'),

                TextColumn::make('error')
                    ->label('Error')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—')
                    ->tooltip(fn(FinancialReport $record): ?string => $record->error),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Filter by Report')
                    ->options(PRFFinancialReportType::getOptions())
                    ->native(false)
                    ->placeholder('All reports'),

                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options(PRFProcessingStatus::getOptions())
                    ->native(false)
                    ->placeholder('All statuses'),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn(FinancialReport $record): string => route('filament.admin.finance.reports.download', [
                        'ulid' => $record->ulid,
                    ]))
                    ->visible(fn(FinancialReport $record): bool => $record->isReady())
                    ->tooltip('Download the generated file'),

                Action::make('email_to_me')
                    ->label('Email to me')
                    ->icon('heroicon-o-envelope')
                    ->action(function (FinancialReport $record): void {
                        Auth::user()?->notify(new FinancialReportReadyNotification($record, emailOnly: true));

                        Notification::make()
                            ->success()
                            ->title('Report emailed')
                            ->body('The report has been sent to your email address.')
                            ->send();
                    })
                    ->visible(
                        fn(FinancialReport $record): bool => $record->isReady()
                        && userCan(FinancialReport::permission('create')),
                    )
                    ->tooltip('Send this report to your own email address'),

                Action::make('regenerate')
                    ->label('Regenerate')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate report')
                    ->modalDescription('Generate this report again from the current cashbook data?')
                    ->action(function (FinancialReport $record): void {
                        $record->update([
                            'status' => PRFProcessingStatus::PENDING,
                            'error' => null,
                            'completed_at' => null,
                        ]);
                        GenerateJob::dispatch($record);

                        Notification::make()
                            ->success()
                            ->title('Regenerating report')
                            ->body(
                                'You’ll get a notification (the bell, top right) with a download link when it’s ready, and a copy by email.',
                            )
                            ->send();
                    })
                    ->visible(
                        fn(FinancialReport $record): bool => (
                            userCan(FinancialReport::permission('create'))
                            && !in_array(
                                $record->status,
                                [PRFProcessingStatus::PENDING, PRFProcessingStatus::PROCESSING],
                                true,
                            )
                        ),
                    )
                    ->tooltip('Generate this report again with the latest cashbook data'),

                DeleteAction::make()->visible(fn(): bool => userCan(FinancialReport::permission('delete'))),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn(): bool => userCan(FinancialReport::permission('delete'))),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->searchPlaceholder('Search reports...')
            ->emptyStateHeading('No financial reports yet')
            ->emptyStateDescription('Generate a cashbook, accountability workbook or summary and it will appear here.')
            ->emptyStateIcon('heroicon-o-document-chart-bar');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFinancialReports::route('/'),
        ];
    }

    public static function canAccess(): bool
    {
        return userCan(FinancialReport::permission('viewAny'));
    }
}
