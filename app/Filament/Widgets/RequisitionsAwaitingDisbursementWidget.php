<?php

namespace App\Filament\Widgets;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
use App\Jobs\Requisition\RecordDisbursementJob;
use App\Models\FinancialAccount;
use App\Models\LedgerEntry;
use App\Models\Requisition;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequisitionsAwaitingDisbursementWidget extends BaseWidget
{
    protected static ?string $heading = 'Requisitions awaiting disbursement';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return userCan(FinancialAccount::permission('viewAny'));
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Requisition::query()
                    ->where('approval_status', PRFApprovalStatus::APPROVED)
                    ->where('total_amount', '>', 0)
                    // Requisitions paid out before the cashbook existed are in the imported history.
                    ->where('approved_at', '>=', FinancialAccount::withTrashed()->min('created_at') ?? now())
                    ->whereNotExists(
                        fn(QueryBuilder $query) => $query
                            ->select(DB::raw('1'))
                            ->from('ledger_entries')
                            ->whereColumn('ledger_entries.requisition_id', 'requisitions.id')
                            ->whereNull('ledger_entries.deleted_at'),
                    )
                    ->with(['member', 'accountingEvent'])
                    ->orderBy('approved_at'),
            )
            ->columns([
                TextColumn::make('ulid')->label('Ref')->copyable()->limit(13),

                TextColumn::make('accountingEvent.name')->label('Event')->limit(30)->placeholder('—'),

                TextColumn::make('member.full_name')->label('Requested by')->placeholder('—'),

                TextColumn::make('responsible_desk')
                    ->label('Desk')
                    ->formatStateUsing(fn(?PRFResponsibleDesk $state): string => $state?->getLabel() ?? '—')
                    ->badge()
                    ->color(fn(?PRFResponsibleDesk $state): string => $state?->getColor() ?? 'gray'),

                TextColumn::make('total_amount')->label('Amount')->money('KES', divideBy: 1),

                TextColumn::make('approved_at')->label('Approved')->date('M j, Y')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('record_disbursement')
                    ->label('Record disbursement')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn(): bool => userCan(LedgerEntry::permission('create')))
                    ->schema([
                        Select::make('financial_account_ulid')
                            ->label('Paid out from')
                            ->options(
                                fn() => FinancialAccount::query()->active()->orderBy('name')->pluck('name', 'ulid'),
                            )
                            ->required()
                            ->searchable()
                            ->native(false),

                        TextInput::make('charge')
                            ->label('Transaction charge')
                            ->prefix('KES')
                            ->integer()
                            ->required()
                            ->default(fn(Requisition $record): int => Utils::estimateTransferCharge((int) $record->total_amount))
                            ->minValue(0)
                            ->helperText('Estimated from the M-Pesa tariff; change it to what was actually charged.'),

                        TextInput::make('reference')->label('Reference')->maxLength(255),

                        DatePicker::make('paid_on')->label('Paid on')->native(false)->maxDate(now())->default(now()),
                    ])
                    ->modalHeading(
                        fn(Requisition $record): string => 'Pay out KES ' . number_format((int) $record->total_amount),
                    )
                    ->modalDescription(
                        fn(Requisition $record): string => (
                            'For '
                            . ($record->accountingEvent?->name ?? 'this requisition')
                            . '. It is booked as a '
                            . ($record->responsible_desk?->getLabel() ?? 'desk')
                            . ' expense and linked to the event.'
                        ),
                    )
                    ->modalSubmitActionLabel('Record payout')
                    ->action(function (Requisition $record, array $data): void {
                        RecordDisbursementJob::dispatchSync(
                            $record->ulid,
                            [
                                'financial_account_ulid' => $data['financial_account_ulid'],
                                'charge' => (int) ($data['charge'] ?? 0),
                                'reference' => $data['reference'] ?? null,
                                'paid_on' => $data['paid_on'] ?? null,
                            ],
                            Auth::id(),
                        );

                        Notification::make()->success()->title('Disbursement recorded')->send();
                    }),
            ])
            ->description('Approved requisitions whose payout hasn’t been recorded in the cashbook yet.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->emptyStateHeading('All approved requisitions are paid out')
            ->emptyStateDescription('Approved requisitions appear here until you record which account paid them.');
    }
}
