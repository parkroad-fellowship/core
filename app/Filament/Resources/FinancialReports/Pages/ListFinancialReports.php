<?php

namespace App\Filament\Resources\FinancialReports\Pages;

use App\Enums\PRFFinancialReportType;
use App\Filament\Resources\FinancialReports\FinancialReportResource;
use App\Jobs\FinancialReport\CreateJob;
use App\Models\FinancialReport;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ListFinancialReports extends ListRecords
{
    protected static string $resource = FinancialReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generate_report')
                ->label('Generate report')
                ->icon('heroicon-o-document-chart-bar')
                ->color('primary')
                ->modalHeading('Generate a report')
                ->modalSubmitActionLabel('Generate')
                ->modalWidth('2xl')
                ->schema([
                    Radio::make('type')
                        ->label('Which report?')
                        ->options(PRFFinancialReportType::getOptions())
                        ->descriptions(
                            collect(PRFFinancialReportType::cases())
                                ->mapWithKeys(fn(PRFFinancialReportType $type): array => [
                                    $type->value => $type->getDescription(),
                                ])->all(),
                        )
                        ->default(PRFFinancialReportType::CASHBOOK->value)
                        ->required(),

                    Select::make('preset')
                        ->label('Period')
                        ->options([
                            'this_month' => 'This month',
                            'last_month' => 'Last month',
                            'year_to_date' => 'Year to date',
                            'last_year' => 'Last year',
                            'custom' => 'Custom…',
                        ])
                        ->default('last_month')
                        ->required()
                        ->live()
                        ->native(false)
                        ->helperText('Which dates the report covers'),

                    DatePicker::make('period_start')
                        ->label('Start Date')
                        ->native(false)
                        ->required(fn(Get $get): bool => $get('preset') === 'custom')
                        ->visible(fn(Get $get): bool => $get('preset') === 'custom'),

                    DatePicker::make('period_end')
                        ->label('End Date')
                        ->native(false)
                        ->required(fn(Get $get): bool => $get('preset') === 'custom')
                        ->afterOrEqual('period_start')
                        ->visible(fn(Get $get): bool => $get('preset') === 'custom'),
                ])
                ->action(function (array $data): void {
                    [$start, $end] = match ($data['preset']) {
                        'this_month' => [now()->startOfMonth(), now()],
                        'last_month' => [
                            now()->startOfMonth()->subMonthNoOverflow(),
                            now()->startOfMonth()->subMonthNoOverflow()->endOfMonth(),
                        ],
                        'year_to_date' => [now()->startOfYear(), now()],
                        'last_year' => [
                            now()->startOfYear()->subYear(),
                            now()->startOfYear()->subYear()->endOfYear(),
                        ],
                        default => [
                            Carbon::parse($data['period_start']),
                            Carbon::parse($data['period_end']),
                        ],
                    };

                    try {
                        CreateJob::dispatchSync([
                            'type' => (int) $data['type'],
                            'period_start' => $start->toDateString(),
                            'period_end' => $end->toDateString(),
                            'requested_by' => Auth::id(),
                        ]);
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Report not queued')
                            ->body($exception->getMessage())
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Generating your report')
                        ->body(
                            'It appears in the list below when ready (usually under a minute), and you’ll get a notification with a download link.',
                        )
                        ->send();
                })
                ->visible(fn(): bool => userCan(FinancialReport::permission('create')))
                ->tooltip('Generate a cashbook, accountability workbook or summary'),
        ];
    }

    public function getSubheading(): ?string
    {
        return 'Workbooks and summaries built from the cashbook. Last month’s accountability workbook and impact summary are generated automatically on the 1st and sent to the treasurer and chair.';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(FinancialReport::permission('viewAny'));
    }
}
