<?php

namespace App\Filament\Widgets;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFResponsibleDesk;
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

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Requisition::query()
                    ->where('approval_status', PRFApprovalStatus::APPROVED)
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
                            ->label('Transaction charge (KES)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        TextInput::make('reference')->label('Reference')->maxLength(255),

                        DatePicker::make('paid_on')->label('Paid on')->native(false)->maxDate(now())->default(now()),
                    ])
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
            ->emptyStateHeading('Nothing awaiting disbursement')
            ->emptyStateDescription('Approved requisitions appear here until their disbursement is posted.');
    }
}
