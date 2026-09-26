<?php

namespace App\Filament\Pages;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFEntryType;
use App\Enums\PRFFinancialReportType;
use App\Enums\PRFReconciliationStatus;
use App\Enums\PRFResponsibleDesk;
use App\Filament\Resources\AccountingEvents\AccountingEventResource;
use App\Jobs\AccountingEvent\UpdateJob;
use App\Jobs\FinancialReport\CreateJob;
use App\Models\AccountingEvent;
use App\Models\AllocationEntry;
use App\Models\ExpenseCategory;
use App\Models\FinancialReport;
use App\Services\Finance\AccountabilityRow;
use App\Services\Finance\AccountabilityService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

/**
 * The treasurer's monthly check that every shilling sent to a mission or event is accounted for:
 * sent − spent + token − refunded should be zero. Mirrors the "Monthly Missions Financials" sheet,
 * for every desk.
 */
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
        return userCan(AccountingEvent::permission('viewAny'));
    }

    public function getSubheading(): ?string
    {
        return 'For every mission and event: money sent, real spending, tokens received and refunds. A balance of zero means it is fully accounted for.';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(AccountingEvent::query()->with(['allocationEntries', 'refunds'])->orderBy('due_date'))
            ->columns([
                TextColumn::make('row_number')->label('#')->rowIndex()->color('gray'),

                TextColumn::make('due_date')->label('Date')->date('j M')->sortable(),

                TextColumn::make('name')
                    ->label('Mission / event')
                    ->limit(40)
                    ->tooltip(fn(AccountingEvent $record): string => $record->name)
                    ->searchable()
                    ->weight('medium')
                    ->description(fn(AccountingEvent $record): ?string => $record->responsible_desk?->getLabel())
                    ->url(fn(AccountingEvent $record): string => AccountingEventResource::getUrl('view', [
                        'record' => $record,
                    ])),

                TextColumn::make('disbursed')
                    ->label('Sent')
                    ->tooltip('Money disbursed through approved requisitions')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->disbursed)
                    ->money('KES', divideBy: 1)
                    ->alignEnd(),

                TextColumn::make('spent')
                    ->label('Spent')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->totalExpenses())
                    ->money('KES', divideBy: 1)
                    ->alignEnd()
                    ->tooltip(fn(AccountingEvent $record): string => $this->spendingBreakdown($record)),

                ...$this->expenseColumns()->map(fn(ExpenseCategory $category) => TextColumn::make('expense_'
                . $category->id)
                    ->label($category->name)
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->expenses[$category->id] ?? 0)
                    ->money('KES', divideBy: 1)
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true))->all(),

                TextColumn::make('transaction_costs')
                    ->label('Transaction costs')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->transactionCosts)
                    ->money('KES', divideBy: 1)
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tokens')
                    ->label('Token')
                    ->tooltip('Tokens of appreciation from the school or host')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->tokens)
                    ->money('KES', divideBy: 1)
                    ->alignEnd(),

                TextColumn::make('to_refund')
                    ->label('To refund')
                    ->tooltip('Sent − spent + token')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->toRefund())
                    ->money('KES', divideBy: 1)
                    ->alignEnd(),

                TextColumn::make('refunded')
                    ->label('Refunded')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->refunded)
                    ->money('KES', divideBy: 1)
                    ->alignEnd(),

                TextColumn::make('balance')
                    ->label('Balance')
                    ->tooltip('To refund − refunded. Zero when fully accounted for.')
                    ->state(fn(AccountingEvent $record): int => $this->rowFor($record)->balance())
                    ->money('KES', divideBy: 1)
                    ->alignEnd()
                    ->color(fn(AccountingEvent $record): string => $this->rowFor($record)->balance() !== 0
                        ? 'danger'
                        : 'success')
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn(AccountingEvent $record): string => $this->rowFor($record)->status()->getLabel())
                    ->badge()
                    ->color(fn(AccountingEvent $record): string => $this->rowFor($record)->status()->getColor())
                    ->description(fn(AccountingEvent $record): ?string => $record->reconciliation_status
                        === PRFReconciliationStatus::PENDING
                            ? 'Suggested'
                            : 'Confirmed'),

                TextColumn::make('remarks')
                    ->label('Remarks')
                    ->state(fn(AccountingEvent $record): string => $this->rowFor($record)->remarks())
                    ->placeholder('—')
                    ->limit(50)
                    ->tooltip(fn(AccountingEvent $record): string => $this->rowFor($record)->remarks())
                    ->wrap(),
            ])
            ->defaultSort('due_date')
            ->filters(
                [
                    SelectFilter::make('month')
                        ->label('Month')
                        ->options($this->monthOptions())
                        ->default($this->defaultMonth()->format('Y-m'))
                        ->selectablePlaceholder(false)
                        ->native(false)
                        ->query(function (Builder $query, array $data): Builder {
                            $month = $this->parseMonth($data['value'] ?? null) ?? $this->defaultMonth();

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

                    // Filters by the status shown in the table (the treasurer's verdict, or the suggestion).
                    SelectFilter::make('status')
                        ->label('Status')
                        ->options(PRFReconciliationStatus::getOptions())
                        ->placeholder('Any status')
                        ->native(false)
                        ->query(function (Builder $query, array $data): Builder {
                            if (blank($data['value'] ?? null)) {
                                return $query;
                            }

                            $status = PRFReconciliationStatus::from((int) $data['value']);
                            $ids = $this
                                ->monthRows()
                                ->filter(fn(AccountabilityRow $row): bool => $row->status() === $status)
                                ->map(fn(AccountabilityRow $row): int => (int) $row->event->getKey());

                            return $query->whereKey($ids->all());
                        }),
                ],
                layout: FiltersLayout::AboveContent,
            )
            ->filtersFormColumns(3)
            ->recordActions([
                Action::make('mark_accounted')
                    ->label('Fully accounted')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->button()
                    ->size('sm')
                    ->visible(
                        fn(AccountingEvent $record): bool => (
                            $this->canReconcile()
                            && $record->reconciliation_status !== PRFReconciliationStatus::FULLY_ACCOUNTED
                        ),
                    )
                    ->requiresConfirmation()
                    ->modalHeading(
                        fn(AccountingEvent $record): string => 'Mark “' . $record->name . '” as fully accounted?',
                    )
                    ->modalDescription(fn(AccountingEvent $record): string => $this->rowFor($record)->balance() === 0
                        ? 'Every shilling sent is accounted for.'
                        : 'The balance is still KES '
                            . number_format($this->rowFor($record)->balance())
                            . '. Only confirm if you have checked it with the desk (for example, the difference was written off).')
                    ->action(fn(AccountingEvent $record) => $this->updateReconciliation($record, [
                        'reconciliation_status' => PRFReconciliationStatus::FULLY_ACCOUNTED->value,
                        'reconciliation_remarks' => null,
                    ])),

                ActionGroup::make([
                    Action::make('needs_attention')
                        ->label('Needs attention…')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->visible(fn(): bool => $this->canReconcile())
                        ->modalHeading('What needs to happen?')
                        ->modalDescription('Your note shows in the Remarks column and in the exported workbook.')
                        ->fillForm(fn(AccountingEvent $record): array => [
                            'reconciliation_remarks' => $record->reconciliation_remarks ?? $this->rowFor(
                                $record,
                            )->remarks(),
                        ])
                        ->schema([
                            Textarea::make('reconciliation_remarks')
                                ->label('Remarks')
                                ->placeholder('e.g. Refund of KES 204 pending from the mission leader')
                                ->required()
                                ->maxLength(1000),
                        ])
                        ->action(fn(AccountingEvent $record, array $data) => $this->updateReconciliation($record, [
                            'reconciliation_status' => PRFReconciliationStatus::NEEDS_ATTENTION->value,
                            'reconciliation_remarks' => $data['reconciliation_remarks'],
                        ])),

                    Action::make('reset_pending')
                        ->label('Clear my verdict')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->visible(
                            fn(AccountingEvent $record): bool => (
                                $this->canReconcile()
                                && $record->reconciliation_status !== PRFReconciliationStatus::PENDING
                            ),
                        )
                        ->requiresConfirmation()
                        ->modalDescription('The status goes back to what the numbers suggest.')
                        ->action(fn(AccountingEvent $record) => $this->updateReconciliation($record, [
                            'reconciliation_status' => PRFReconciliationStatus::PENDING->value,
                            'reconciliation_remarks' => null,
                        ])),

                    Action::make('open_event')
                        ->label('Open requisitions & refunds')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn(AccountingEvent $record): string => AccountingEventResource::getUrl('view', [
                            'record' => $record,
                        ]))
                        ->openUrlInNewTab(),
                ]),
            ])
            ->toolbarActions([
                BulkAction::make('mark_accounted')
                    ->label('Mark fully accounted')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(): bool => $this->canReconcile())
                    ->authorize(fn(): bool => $this->canReconcile())
                    ->requiresConfirmation()
                    ->modalDescription(
                        'Only rows with a zero balance are marked; the others are left for you to check.',
                    )
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
                                'reconciliation_remarks' => null,
                                'reconciled_by' => Auth::id(),
                            ], $record->ulid);

                            $applied++;
                        }

                        $this->accountabilityRows = [];

                        Notification::make()
                            ->success()
                            ->title("Marked {$applied} as fully accounted")
                            ->body($skipped > 0 ? "{$skipped} skipped because their balance isn’t zero." : null)
                            ->send();
                    }),
            ])
            ->striped()
            ->emptyStateIcon('heroicon-o-clipboard-document-check')
            ->emptyStateHeading('Nothing to account for this month')
            ->emptyStateDescription(
                'Missions and events due in the selected month appear here once they have an accounting event. Try another month or desk.',
            );
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('export_month')
                    ->label('This month')
                    ->action(fn() => $this->export(
                        $this->selectedMonth()->copy()->startOfMonth(),
                        $this->selectedMonth()->copy()->endOfMonth(),
                    )),

                Action::make('export_ytd')
                    ->label('Year to date')
                    ->action(fn() => $this->export(
                        $this->selectedMonth()->copy()->startOfYear(),
                        $this->selectedMonth()->copy()->endOfMonth(),
                    )),
            ])
                ->label('Download as Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->button()
                ->visible(fn(): bool => userCan(FinancialReport::permission('create'))),
        ];
    }

    /**
     * Headline numbers for the selected month and desk, shown above the table.
     *
     * @return list<array{label: string, value: string, hint: string, color: string}>
     */
    public function getAccountabilityStats(): array
    {
        $rows = $this->monthRows()->when($this->selectedDesk(), fn(
            Collection $rows,
            PRFResponsibleDesk $desk,
        ) => $rows->filter(fn(AccountabilityRow $row): bool => $row->event->responsible_desk === $desk));

        $accounted = $rows->filter(
            fn(AccountabilityRow $row): bool => $row->status() === PRFReconciliationStatus::FULLY_ACCOUNTED,
        )->count();
        $attention = $rows->filter(
            fn(AccountabilityRow $row): bool => $row->status() === PRFReconciliationStatus::NEEDS_ATTENTION,
        )->count();
        $outstanding = $rows->sum(fn(AccountabilityRow $row): int => max(0, $row->balance()));

        return [
            [
                'label' => 'Fully accounted',
                'value' => $accounted . ' of ' . $rows->count(),
                'hint' => $this->selectedMonth()->format('F Y'),
                'color' => 'success',
            ],
            [
                'label' => 'Need attention',
                'value' => (string) $attention,
                'hint' => 'Balance isn’t zero or accounting is missing',
                'color' => $attention > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Sent',
                'value' => 'KES ' . number_format($rows->sum(fn(AccountabilityRow $row): int => $row->disbursed)),
                'hint' => 'Through approved requisitions',
                'color' => 'gray',
            ],
            [
                'label' => 'Really spent',
                'value' => 'KES ' . number_format($rows->sum(fn(AccountabilityRow $row): int => $row->totalExpenses())),
                'hint' => 'From receipts and expense entries',
                'color' => 'gray',
            ],
            [
                'label' => 'Refunds outstanding',
                'value' => 'KES ' . number_format($outstanding),
                'hint' => 'Still to come back to the fellowship',
                'color' => $outstanding > 0 ? 'warning' : 'success',
            ],
        ];
    }

    public function selectedMonth(): Carbon
    {
        return $this->parseMonth($this->getTableFilterState('month')['value'] ?? null) ?? $this->defaultMonth();
    }

    protected function rowFor(AccountingEvent $record): AccountabilityRow
    {
        return $this->accountabilityRows[(int) $record->getKey()] ??= app(AccountabilityService::class)->row($record);
    }

    /**
     * Every row of the selected month, computed once per request and shared with the table.
     *
     * @return Collection<int, AccountabilityRow>
     */
    protected function monthRows(): Collection
    {
        $month = $this->selectedMonth();
        $rows = app(AccountabilityService::class)->forMonth($month);

        foreach ($rows as $row) {
            $this->accountabilityRows[(int) $row->event->getKey()] ??= $row;
        }

        return $rows;
    }

    /**
     * Expense categories used anywhere in the accounts (plus active ones), so the breakdown
     * columns don't depend on which month is selected.
     *
     * @return Collection<int, ExpenseCategory>
     */
    protected function expenseColumns(): Collection
    {
        $used = AllocationEntry::query()
            ->where('entry_type', PRFEntryType::DEBIT)
            ->whereNotNull('expense_category_id')
            ->distinct()
            ->pluck('expense_category_id');

        return ExpenseCategory::query()
            ->where(fn(Builder $query) => $query->where('is_active', PRFActiveStatus::ACTIVE)->orWhereIn('id', $used))
            ->orderBy('id')
            ->get();
    }

    protected function spendingBreakdown(AccountingEvent $record): string
    {
        $row = $this->rowFor($record);

        if ($row->totalExpenses() === 0) {
            return 'No spending recorded yet';
        }

        $names = ExpenseCategory::query()
            ->whereIn('id', array_filter(array_keys($row->expenses)))
            ->pluck('name', 'id');

        return collect($row->expenses)
            ->map(
                fn(int $amount, $categoryId): string => ($names[$categoryId] ?? 'Uncategorised')
                . ': KES '
                . number_format($amount),
            )
            ->push('Transaction costs: KES ' . number_format($row->transactionCosts))
            ->implode("\n");
    }

    protected function selectedDesk(): ?PRFResponsibleDesk
    {
        $value = $this->getTableFilterState('responsible_desk')['value'] ?? null;

        return blank($value) ? null : PRFResponsibleDesk::tryFrom((int) $value);
    }

    protected function defaultMonth(): Carbon
    {
        return now()->startOfMonth()->subMonthNoOverflow();
    }

    protected function parseMonth(mixed $value): ?Carbon
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}$/', $value) === 1
            ? Carbon::createFromFormat('!Y-m', $value)->startOfMonth()
            : null;
    }

    /**
     * @return array<string, string>
     */
    protected function monthOptions(): array
    {
        $options = [];
        $first = now()->startOfMonth();

        for ($i = 0; $i < 24; $i++) {
            $month = $first->copy()->subMonthsNoOverflow($i);
            $options[$month->format('Y-m')] = $month->format('F Y');
        }

        return $options;
    }

    protected function canReconcile(): bool
    {
        return userCan(AccountingEvent::permission('edit'));
    }

    protected function export(Carbon $from, Carbon $to): void
    {
        CreateJob::dispatchSync([
            'type' => PRFFinancialReportType::MONTHLY_ACCOUNTABILITY->value,
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'requested_by' => Auth::id(),
        ]);

        Notification::make()
            ->success()
            ->title('Generating the accountability workbook')
            ->body('We’ll email it to you shortly. It will also be under Financial Reports.')
            ->send();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function updateReconciliation(AccountingEvent $record, array $attributes): void
    {
        UpdateJob::dispatchSync([...$attributes, 'reconciled_by' => Auth::id()], $record->ulid);

        unset($this->accountabilityRows[(int) $record->getKey()]);

        Notification::make()->success()->title('Saved')->send();
    }
}
