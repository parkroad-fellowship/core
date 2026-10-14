<?php

namespace App\Filament\Resources\AccountingEvents\RelationManagers;

use App\Jobs\Refund\CreateJob;
use App\Models\AccountingEvent;
use App\Models\FinancialAccount;
use App\Models\Refund;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Money returned after an event. Recording a refund also books it in the cashbook
 * (Paybill unless another account is picked) via Refund\CreateJob.
 */
class RefundsRelationManager extends RelationManager
{
    protected static string $relationship = 'refunds';

    protected static ?string $title = 'Refunds';

    protected static ?string $modelLabel = 'Refund';

    protected static ?string $pluralModelLabel = 'Refunds';

    /**
     * Refunds are recorded from the event's page (the Monthly Accountability page links here).
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    /**
     * Fields for recording a refund, reused by the mission accounting table action.
     *
     * @return array<int, mixed>
     */
    public static function refundFields(): array
    {
        return [
            TextInput::make('amount')
                ->label('Amount refunded')
                ->required()
                ->integer()
                ->minValue(1)
                ->prefix('KES')
                ->helperText(
                    'Unspent money returned to the fellowship, including any token of appreciation handed over with it.',
                ),

            Select::make('financial_account_ulid')
                ->label('Received in')
                ->options(
                    fn(): array => FinancialAccount::query()->active()->orderBy('name')->pluck('name', 'ulid')->all(),
                )
                ->searchable()
                ->preload()
                ->placeholder('Paybill (default)')
                ->helperText('The account the money came back into. Most refunds come through the Paybill.'),

            Textarea::make('confirmation_message')
                ->label('Confirmation message')
                ->required()
                ->rows(3)
                ->placeholder('Paste the M-Pesa or bank confirmation message')
                ->columnSpanFull(),
        ];
    }

    /**
     * "Record refund" for accounting event rows outside this relation manager.
     */
    public static function recordRefundAction(): Action
    {
        return Action::make('record_refund')
            ->label('Record refund')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('success')
            ->schema(static::refundFields())
            ->action(fn(array $data, AccountingEvent $record): Refund => CreateJob::dispatchSync([
                'accounting_event_ulid' => $record->ulid,
                'amount' => $data['amount'],
                'confirmation_message' => $data['confirmation_message'],
                'financial_account_ulid' => $data['financial_account_ulid'] ?? null,
            ]))
            ->successNotificationTitle('Refund recorded')
            ->visible(fn(): bool => userCan(Refund::permission('create')));
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(static::refundFields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')->label('Amount (KES)')->money('KES', divideBy: 1)->sortable(),

                TextColumn::make('charge')->label('Charge (KES)')->money('KES', divideBy: 1)->toggleable(),

                TextColumn::make('deficit_amount')
                    ->label('Deficit (KES)')
                    ->money('KES', divideBy: 1)
                    ->toggleable()
                    ->tooltip('What the person still owes after this refund'),

                TextColumn::make('confirmation_message')
                    ->label('Confirmation')
                    ->limit(60)
                    ->wrap()
                    ->toggleable()
                    ->tooltip(fn(Refund $record): ?string => $record->confirmation_message),

                TextColumn::make('financialAccount.name')->label('Account')->placeholder('Paybill')->toggleable(),

                TextColumn::make('created_at')->label('Date')->date('M j, Y')->sortable(),
            ])
            ->filters([
                TrashedFilter::make()->label('Deleted Records')->placeholder('All Records'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Record refund')
                    ->icon('heroicon-o-plus')
                    ->using(fn(array $data): Refund => CreateJob::dispatchSync([
                        ...$data,
                        'accounting_event_ulid' => $this->getOwnerRecord()->ulid,
                    ]))
                    ->visible(fn(): bool => userCan(Refund::permission('create'))),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete refund')
                    ->modalDescription(
                        'The refund and its cashbook line are removed, and the event’s balance goes back up. You can restore both from the Deleted filter.',
                    )
                    ->visible(fn(): bool => userCan(Refund::permission('delete'))),

                RestoreAction::make()->visible(fn(): bool => userCan(Refund::permission('restore'))),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn(): bool => userCan(Refund::permission('delete'))),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-arrow-uturn-left')
            ->emptyStateHeading('No refunds yet')
            ->emptyStateDescription(
                'Record money returned after the mission or event so its accountability balance reaches zero.',
            )
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
