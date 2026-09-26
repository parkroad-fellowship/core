<?php

namespace App\Filament\Resources\LedgerEntries\Pages;

use App\Enums\PRFLedgerChannel;
use App\Enums\PRFPledgeStatus;
use App\Filament\Resources\LedgerEntries\LedgerEntryResource;
use App\Jobs\LedgerEntry\CreateJob;
use App\Models\AccountingEvent;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use App\Models\LedgerEntry;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Pledge;
use App\Rules\PhoneNumber;
use App\Services\Finance\ReceiptDocument;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Receipts income with a receipt number: cash gifts, contributions and subscriptions.
 */
class ReceiptIncome extends CreateRecord
{
    use HasWizard;

    protected static string $resource = LedgerEntryResource::class;

    /**
     * @return array<int, Step>
     */
    public function getSteps(): array
    {
        return [
            Step::make('Money received')
                ->icon('heroicon-o-banknotes')
                ->description('When, where and how much')
                ->columns(2)
                ->schema([
                    DatePicker::make('transacted_on')
                        ->label('Date received')
                        ->required()
                        ->default(now())
                        ->maxDate(now())
                        ->native(false)
                        ->helperText('Cannot be in the future.'),

                    Select::make('financial_account_ulid')
                        ->label('Received into')
                        ->options(fn(): array => LedgerEntryResource::accountOptions())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $type = FinancialAccount::query()->where('ulid', $state)->first()?->type;

                            if ($type !== null) {
                                $set('channel', PRFLedgerChannel::defaultFor($type)->value);
                            }
                        })
                        ->helperText('The account the money landed in.'),

                    Select::make('channel')
                        ->label('Channel')
                        ->options(PRFLedgerChannel::getOptions())
                        ->required()
                        ->native(false)
                        ->helperText('How the money moved.'),

                    TextInput::make('amount')
                        ->label('Amount (KES)')
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->step(1)
                        ->prefix('KES')
                        ->placeholder('e.g., 1000'),

                    TextInput::make('reference')
                        ->label('Reference')
                        ->maxLength(255)
                        ->placeholder('M-Pesa code, bank slip or cheque no.')
                        ->columnSpanFull(),
                ]),

            Step::make('Designated for')
                ->icon('heroicon-o-tag')
                ->description('What the gift is for')
                ->columns(2)
                ->schema([
                    Select::make('ledger_category_ulid')
                        ->label('Designated for')
                        ->options(fn(): array => LedgerEntryResource::incomeCategoryOptions())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->helperText('e.g. Missions, Camp, Member Contribution – Give.')
                        ->columnSpanFull(),

                    Select::make('pledge_ulid')
                        ->label('Pledge (optional)')
                        ->options(
                            fn(): array => Pledge::query()
                                ->where('status', PRFPledgeStatus::ACTIVE->value)
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn(Pledge $pledge): array => [
                                    $pledge->ulid => "{$pledge->name} — KES " . number_format($pledge->amount),
                                ])
                                ->all(),
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            if (blank($state)) {
                                return;
                            }

                            $pledge = Pledge::query()->where('ulid', $state)->first();

                            if ($pledge === null) {
                                return;
                            }

                            $set('counterparty', $pledge->name);
                            $set('giver_email', $pledge->email);
                            $set('giver_phone', $pledge->phone);
                        })
                        ->helperText('Picking a pledge fills the giver below.'),

                    Select::make('membership_ulid')
                        ->label('Membership (optional)')
                        ->options(
                            fn(): array => Membership::query()
                                ->where('approved', false)
                                ->with('member')
                                ->get()
                                ->mapWithKeys(fn(Membership $membership): array => [
                                    $membership->ulid =>
                                        ($membership->member?->full_name ?? 'Unknown')
                                            . ' — '
                                            . $membership->type?->getLabel(),
                                ])
                                ->all(),
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            if (blank($state)) {
                                return;
                            }

                            $membership = Membership::query()->where('ulid', $state)->with('member')->first();

                            if ($membership === null) {
                                return;
                            }

                            $subscription = LedgerCategory::query()
                                ->where('code', 'income.member_subscription')
                                ->first();

                            if ($subscription !== null) {
                                $set('ledger_category_ulid', $subscription->ulid);
                            }

                            $set('amount', $membership->type?->getPrice() ?? $get('amount'));
                            $set('member_ulid', $membership->member?->ulid);
                            $set('counterparty', $membership->member?->full_name);
                            $set('giver_email', $membership->member?->personal_email);
                            $set('giver_phone', $membership->member?->phone_number);
                        })
                        ->helperText('Sets the category to Member Subscription and the fee amount.'),

                    Select::make('accounting_event_ulid')
                        ->label('Accounting event (optional)')
                        ->options(
                            fn(): array => AccountingEvent::query()
                                ->orderByDesc('due_date')
                                ->limit(100)
                                ->pluck('name', 'ulid')
                                ->all(),
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->helperText('Only for tokens of appreciation.'),

                    Textarea::make('description')->label('Description')->rows(2)->columnSpanFull(),
                ]),

            Step::make('Giver')
                ->icon('heroicon-o-user')
                ->description('Who gave, and where to send the receipt')
                ->columns(2)
                ->schema([
                    Select::make('member_ulid')
                        ->label('Member (optional)')
                        ->options(
                            fn(): array => Member::query()->orderBy('full_name')->pluck('full_name', 'ulid')->all(),
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            if (blank($state)) {
                                return;
                            }

                            $member = Member::query()->where('ulid', $state)->first();

                            if ($member === null) {
                                return;
                            }

                            $set('counterparty', $member->full_name);
                            $set('giver_email', $member->personal_email);
                            $set('giver_phone', $member->phone_number);
                        })
                        ->helperText('Fills the name and contacts below.')
                        ->columnSpanFull(),

                    TextInput::make('counterparty')
                        ->label('Giver name')
                        ->maxLength(255)
                        ->required(fn(Get $get): bool => blank($get('member_ulid')))
                        ->placeholder('Required when no member is picked.'),

                    Section::make('Receipt contacts')
                        ->columnSpanFull()
                        ->description('Receipts go here when "Send receipt now" is on.')
                        ->schema([
                            TextInput::make('giver_email')->label('Email')->email()->maxLength(255),

                            TextInput::make('giver_phone')->label('Phone')->maxLength(255)->rule(new PhoneNumber()),

                            Toggle::make('send_receipt')
                                ->label('Send receipt now')
                                ->default(true)
                                ->helperText('Emails the PDF and texts a link when a contact exists.')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return CreateJob::dispatchSync([...$data, 'recorded_by' => Auth::id()]);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        /** @var LedgerEntry $entry */
        $entry = $this->getRecord();

        return Notification::make()
            ->success()
            ->title("Receipt {$entry->receipt_number} recorded")
            ->body(
                'KES '
                . number_format($entry->amount)
                . ' received into '
                . ($entry->financialAccount?->name ?? 'the account')
                . '.',
            )
            ->actions([
                Action::make('view_receipt')->label(
                    'View receipt',
                )->url(fn(): string => LedgerEntryResource::getUrl('view', ['record' => $entry])),

                Action::make('share_whatsapp')
                    ->label('Share on WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn(): string => app(ReceiptDocument::class)->whatsAppURL($entry), shouldOpenInNewTab: true),

                Action::make('receipt_another')->label('Receipt another')->url(
                    fn(): string => LedgerEntryResource::getUrl('receipt'),
                ),
            ]);
    }

    public function getTitle(): string
    {
        return 'Receipt income';
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(LedgerEntry::permission('create'));
    }
}
