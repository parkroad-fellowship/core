<?php

namespace App\Filament\Pages;

use App\Enums\PRFFinancialReportType;
use App\Enums\PRFReconciliationStatus;
use App\Enums\PRFResponsibleDesk;
use App\Filament\Resources\AccountingEvents\AccountingEventResource;
use App\Jobs\AccountingEvent\UpdateJob;
use App\Jobs\FinancialReport\CreateJob;
use App\Models\AccountingEvent;
use App\Services\Finance\AccountabilityRow;
use App\Services\Finance\AccountabilityService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class MonthlyAccountability extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $title = 'Monthly Accountability';

    protected static ?string $navigationLabel = 'Monthly Accountability';

    protected static string|UnitEnum|null $navigationGroup = 'Treasurer';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.monthly-accountability';

    /** @var array<int, AccountabilityRow> */
    protected array $accountabilityRows = [];

    public static function canAccess(): bool
    {
        return (bool) Auth::user()?->can(AccountingEvent::permission('viewAny'));
    }

    public function table(Table $table): Table
    {
        $service = app(AccountabilityService::class);
        $month = $this->selectedMonth();
        $columns = $service->expenseColumns($service->between(
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth(),
        ));

        return $table
            ->query(
                AccountingEvent::query()
                    ->with(['allocationEntries', 'refunds', 'accountingEventable'])
                    ->orderBy('due_date'),
            )
            ->columns([
                TextColumn::make('row_number')->label('S/No')->rowIndex(),

                TextColumn::make('due_date')->label('Date')->date('M j, Y')->sortable(),

                TextColumn::make('name')
                    ->label('Event')
                    ->limit(30)
                    ->searchable()
                    ->url(fn(AccountingEvent $record): string => AccountingEventResource::getUrl('view', [
                        'record' => $record,
                    ])),

                TextColumn::make('responsible_desk')
                    ->label('Desk')
                    ->formatStateUsing(fn(?PRFResponsibleDesk $state): string => $state?->getLabel() ?? '—')
                    ->badge()
                    ->color(fn(?PRFResponsibleDesk $state): string => $state?->getColor() ?? 'gray'),

                TextColumn::make('disbursed')
                    ->label('Disbursed')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->disbursed)
                    ->money('KES', divideBy: 1),

                ...$columns->map(fn($category) => TextColumn::make('expense_' . $category->id)
                    ->label($category->name)
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->expenses[$category->id] ?? 0)
                    ->money('KES', divideBy: 1))->all(),

                TextColumn::make('transaction_costs')
                    ->label('Transaction costs')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->transactionCosts)
                    ->money('KES', divideBy: 1),

                TextColumn::make('tokens')
                    ->label('Token')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->tokens)
                    ->money('KES', divideBy: 1),

                TextColumn::make('to_refund')
                    ->label('To refund')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->toRefund())
                    ->money('KES', divideBy: 1),

                TextColumn::make('refunded')
                    ->label('Refund done')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->refunded)
                    ->money('KES', divideBy: 1),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->balance())
                    ->money('KES', divideBy: 1)
                    ->color(fn(AccountingEvent $record): string => $this->rowFor($record)->balance() !== 0
                        ? 'danger'
                        : 'success')
                    ->weight('bold'),

                TextColumn::make('reconciliation_status')
                    ->label('Status')
                    ->state(fn(AccountingEvent $record): string => $this->rowFor($record)->status()->getLabel())
                    ->badge()
                    ->color(fn(AccountingEvent $record): string => $this->rowFor($record)->status()->getColor()),

                TextColumn::make('reconciliation_remarks')
                    ->label('Remarks')
                    ->state(fn(AccountingEvent $record): string => $this->rowFor($record)->remarks())
                    ->limit(40)
                    ->wrap(),
            ])
            ->defaultSort('due_date')
            ->filters([
                SelectFilter::make('month')
                    ->label('Month')
                    ->options($this->monthOptions())
                    ->default(now()->subMonth()->format('Y-m'))
                    ->native(false)
                    ->query(function (Builder $query, array $data): Builder {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        $month = Carbon::createFromFormat('Y-m', $data['value']);

                        return $query->whereBetween('due_date', [
                            $month->copy()->startOfMonth()->toDateString(),
                            $month->copy()->endOfMonth()->toDateString(),
                        ]);
                    }),

                SelectFilter::make('responsible_desk')
                    ->label('Desk')
                    ->options(PRFResponsibleDesk::getOptions())
                    ->placeholder('All desks')
                    ->native(false),

                SelectFilter::make('reconciliation_status')
                    ->label('Status')
                    ->options(PRFReconciliationStatus::getOptions())
                    ->placeholder('All statuses')
                    ->native(false),
            ])
            ->recordActions([
                Action::make('mark_accounted')
                    ->label('Mark fully accounted')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(
                        fn(AccountingEvent $record): bool => (
                            $this->canReconcile()
                            && $this->rowFor($record)->status() !== PRFReconciliationStatus::FULLY_ACCOUNTED
                        ),
                    )
                    ->requiresConfirmation()
                    ->action(fn(AccountingEvent $record): mixed => $this->updateReconciliation($record, [
                        'reconciliation_status' => PRFReconciliationStatus::FULLY_ACCOUNTED->value,
                    ])),

                Action::make('needs_attention')
                    ->label('Needs attention')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn(): bool => $this->canReconcile())
                    ->schema([
                        Textarea::make('reconciliation_remarks')->label('Remarks')->required()->maxLength(1000),
                    ])
                    ->action(fn(AccountingEvent $record, array $data): mixed => $this->updateReconciliation($record, [
                        'reconciliation_status' => PRFReconciliationStatus::NEEDS_ATTENTION->value,
                        'reconciliation_remarks' => $data['reconciliation_remarks'],
                    ])),

                Action::make('reset_pending')
                    ->label('Reset to pending')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->visible(fn(): bool => $this->canReconcile())
                    ->requiresConfirmation()
                    ->action(fn(AccountingEvent $record): mixed => $this->updateReconciliation($record, [
                        'reconciliation_status' => PRFReconciliationStatus::PENDING->value,
                        'reconciliation_remarks' => null,
                    ])),

                Action::make('open_event')
                    ->label('Open requisitions / refunds')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn(AccountingEvent $record): string => AccountingEventResource::getUrl('view', [
                        'record' => $record,
                    ]))
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkAction::make('mark_accounted')
                    ->label('Mark fully accounted')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->deselectRecordsAfterCompletion()
                    ->action(function (EloquentCollection $records): void {
                        $applied = 0;
                        $skipped = 0;

                        foreach ($records as $record) {
                            if ($this->rowFor($record)->balance() !== 0) {
                                $skipped++;

                                continue;
                            }

                            UpdateJob::dispatchSync([
                                'reconciliation_status' => PRFReconciliationStatus::FULLY_ACCOUNTED->value,
                                'reconciled_by' => Auth::id(),
                            ], $record->ulid);

                            $applied++;
                        }

                        Notification::make()
                            ->success()
                            ->title("Marked {$applied} as fully accounted")
                            ->body($skipped > 0 ? "{$skipped} skipped: balance is not zero." : null)
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No events this month')
            ->emptyStateDescription('Accounting events due in the selected month appear here.');
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_month')
                ->label('Export month')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function (): void {
                    $month = $this->selectedMonth();

                    CreateJob::dispatchSync([
                        'type' => PRFFinancialReportType::MONTHLY_ACCOUNTABILITY->value,
                        'period_start' => $month->copy()->startOfMonth()->toDateString(),
                        'period_end' => $month->copy()->endOfMonth()->toDateString(),
                        'requested_by' => Auth::id(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Generating monthly accountability…')
                        ->body("You'll get an email when it's ready.")
                        ->send();
                }),

            Action::make('export_ytd')
                ->label('Export year to date')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function (): void {
                    $month = $this->selectedMonth();

                    CreateJob::dispatchSync([
                        'type' => PRFFinancialReportType::MONTHLY_ACCOUNTABILITY->value,
                        'period_start' => $month->copy()->startOfYear()->toDateString(),
                        'period_end' => $month->copy()->endOfMonth()->toDateString(),
                        'requested_by' => Auth::id(),
                    ]);

                    Notification::make()
                        ->success()
                        ->title('Generating year-to-date accountability…')
                        ->body("You'll get an email when it's ready.")
                        ->send();
                }),
        ];
    }

    /**
     * Stats for the selected month, rendered above the table.
     *
     * @return list<array{label: string, value: string}>
     */
    public function getAccountabilityStats(): array
    {
        $month = $this->selectedMonth();

        $rows = app(AccountabilityService::class)->between(
            $month->copy()->startOfMonth(),
            $month->copy()->endOfMonth(),
            $this->selectedDesk(),
        );

        $fullyAccounted = $rows->filter(
            fn(AccountabilityRow $row): bool => $row->status() === PRFReconciliationStatus::FULLY_ACCOUNTED,
        )->count();

        $needsAttention = $rows->filter(
            fn(AccountabilityRow $row): bool => $row->status() === PRFReconciliationStatus::NEEDS_ATTENTION,
        )->count();

        return [
            ['label' => 'Events', 'value' => number_format($rows->count())],
            ['label' => 'Fully accounted', 'value' => number_format($fullyAccounted)],
            ['label' => 'Needing attention', 'value' => number_format($needsAttention)],
            ['label' => 'Total disbursed', 'value' => 'KES ' . number_format($rows->sum->disbursed)],
            [
                'label' => 'Total real expenses',
                'value' => 'KES ' . number_format($rows->sum(fn(AccountabilityRow $row): int => $row->totalExpenses())),
            ],
            [
                'label' => 'Refunds pending',
                'value' =>
                    'KES ' . number_format($rows->sum(fn(AccountabilityRow $row): int => max(0, $row->balance()))),
            ],
        ];
    }

    protected function rowFor(AccountingEvent $record): AccountabilityRow
    {
        return $this->accountabilityRows[$record->getKey()] ??= app(AccountabilityService::class)->row($record);
    }

    protected function selectedMonth(): Carbon
    {
        $value = $this->getTableFilterState('month')['value'] ?? null;

        if (is_string($value) && $value !== '') {
            return Carbon::createFromFormat('Y-m', $value)->startOfMonth();
        }

        return now()->subMonth()->startOfMonth();
    }

    protected function selectedDesk(): ?PRFResponsibleDesk
    {
        $value = $this->getTableFilterState('responsible_desk')['value'] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        return PRFResponsibleDesk::fromValue((int) $value);
    }

    /**
     * @return array<string, string>
     */
    protected function monthOptions(): array
    {
        $options = [];

        for ($i = 0; $i < 24; $i++) {
            $month = now()->subMonths($i);
            $options[$month->format('Y-m')] = $month->format('M Y');
        }

        return $options;
    }

    protected function canReconcile(): bool
    {
        return userCan(AccountingEvent::permission('edit'));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function updateReconciliation(AccountingEvent $record, array $attributes): AccountingEvent
    {
        $event = UpdateJob::dispatchSync([
            ...$attributes,
            'reconciled_by' => Auth::id(),
        ], $record->ulid);

        unset($this->accountabilityRows[$record->getKey()]);

        Notification::make()->success()->title('Accountability updated')->send();

        return $event;
    }
}
