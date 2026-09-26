<?php

namespace App\Filament\Resources\LedgerEntries\RelationManagers;

use App\Jobs\ReceiptDelivery\CreateJob;
use App\Models\ReceiptDelivery;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use InvalidArgumentException;

/**
 * Read-only log of receipt sends for one cashbook line, with a resend action.
 */
class ReceiptDeliveriesRelationManager extends RelationManager
{
    protected static string $relationship = 'receiptDeliveries';

    protected static ?string $recordTitleAttribute = 'recipient';

    protected static ?string $title = 'Receipt deliveries';

    protected static ?string $modelLabel = 'Delivery';

    protected static ?string $pluralModelLabel = 'Deliveries';

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('channel')
                ->label('Channel')
                ->badge()
                ->formatStateUsing(fn($state) => $state?->getLabel())
                ->icon(fn($state) => $state?->getIcon()),

            TextColumn::make('recipient')->label('Recipient')->searchable()->copyable(),

            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn($state) => $state?->getLabel())
                ->color(fn($state) => $state?->getColor()),

            TextColumn::make('sent_at')->label('Sent at')->dateTime('M j, Y g:i A')->placeholder('—')->sortable(),

            TextColumn::make('error')
                ->label('Error')
                ->placeholder('—')
                ->limit(60)
                ->tooltip(fn($state): ?string => filled($state) ? (string) $state : null),

            TextColumn::make('created_at')
                ->label('Requested on')
                ->dateTime('M j, Y g:i A')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ])->recordActions([
            Action::make('resend')
                ->label('Resend')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Resend receipt')
                ->modalDescription(
                    fn(ReceiptDelivery $record): string => "Send this receipt again by {$record->channel->getLabel()} to {$record->recipient}?",
                )
                ->action(function (ReceiptDelivery $record): void {
                    try {
                        $delivery = CreateJob::dispatchSync([
                            'ledger_entry_ulid' => $record->ledgerEntry?->ulid ?? $this->getOwnerRecord()->ulid,
                            'channel' => $record->channel->value,
                            'recipient' => $record->recipient,
                            'requested_by' => auth()->id(),
                        ]);
                    } catch (InvalidArgumentException $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Receipt not resent')
                            ->body($exception->getMessage())
                            ->send();

                        return;
                    }

                    if ($delivery->share_url !== null) {
                        Notification::make()
                            ->success()
                            ->title('WhatsApp message ready')
                            ->body('Open WhatsApp to send it from your phone.')
                            ->actions([
                                Action::make('open_whatsapp')
                                    ->label('Open WhatsApp')
                                    ->button()
                                    ->url($delivery->share_url, shouldOpenInNewTab: true),
                            ])
                            ->persistent()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Receipt on its way')
                        ->body("Sending by {$record->channel->getLabel()} to {$record->recipient}.")
                        ->send();
                })
                ->visible(fn(): bool => userCan(ReceiptDelivery::permission('create'))),
        ])->defaultSort('created_at', 'desc');
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
