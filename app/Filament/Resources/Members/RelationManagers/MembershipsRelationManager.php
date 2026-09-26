<?php

namespace App\Filament\Resources\Members\RelationManagers;

use App\Enums\PRFLedgerCategoryKind;
use App\Enums\PRFMembershipType;
use App\Jobs\LedgerEntry\CreateJob;
use App\Models\FinancialAccount;
use App\Models\LedgerCategory;
use App\Models\LedgerEntry;
use App\Models\Membership;
use App\Services\Finance\ChartOfAccounts;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MembershipsRelationManager extends RelationManager
{
    protected static string $relationship = 'memberships';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $label = 'Membership';

    protected static ?string $pluralLabel = 'Memberships';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('📋 Membership Details')
                ->columnSpanFull()
                ->description('Annual membership information and payment details')
                ->schema([
                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            Select::make('spiritual_year_id')
                                ->label('📅 Spiritual Year')
                                ->helperText('Select the spiritual year for this membership')
                                ->relationship(name: 'spiritualYear', titleAttribute: 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->native(false),

                            Select::make('type')
                                ->label('🎫 Membership Type')
                                ->helperText('Select the type of membership')
                                ->required()
                                ->options(PRFMembershipType::getFilterOptions())
                                ->default(PRFMembershipType::FRIEND->value)
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    $membershipType = PRFMembershipType::fromValue($state);
                                    $set('amount', $membershipType->getPrice());
                                }),
                        ]),

                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            TextInput::make('amount')
                                ->label('💰 Membership Fee')
                                ->helperText('Fee amount for this membership type')
                                ->numeric()
                                ->prefix('KES')
                                ->disabled()
                                ->dehydrated(),

                            Toggle::make('approved')
                                ->label('✅ Approved')
                                ->helperText('Mark membership as approved')
                                ->inline(false),
                        ]),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('spiritual_year.name')
            ->columns([
                TextColumn::make('spiritualYear.name')
                    ->label('📅 Spiritual Year')
                    ->sortable()
                    ->searchable()
                    ->weight('medium')
                    ->tooltip('Spiritual year for this membership'),

                TextColumn::make('type')
                    ->badge()
                    ->label('🎫 Type')
                    ->formatStateUsing(fn($record) => $record->type?->getLabel())
                    ->color(fn($record) => $record->type?->getColor())
                    ->icon(fn($record) => $record->type?->getIcon())
                    ->sortable()
                    ->tooltip('Membership type and level'),

                TextColumn::make('amount')
                    ->label('💰 Fee')
                    ->money('KES')
                    ->sortable()
                    ->color(fn($record) => $record->amount > 0 ? 'success' : 'gray')
                    ->tooltip('Membership fee amount'),

                IconColumn::make('approved')
                    ->label('✅ Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->sortable()
                    ->tooltip(fn($record) => $record->approved ? 'Membership approved' : 'Pending approval'),

                TextColumn::make('created_at')
                    ->label('📅 Registered')
                    ->dateTime('M j, Y')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Date membership was registered'),

                TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),
            ])
            ->filters([
                PRFMembershipType::getTableFilter(),

                SelectFilter::make('spiritual_year')
                    ->label('Spiritual Year')
                    ->relationship('spiritualYear', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('approved')
                    ->label('Approval Status')
                    ->placeholder('All memberships')
                    ->trueLabel('Approved only')
                    ->falseLabel('Pending approval'),

                Filter::make('amount_range')
                    ->label('Fee Range')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('min_amount')->label('Minimum Fee')->numeric()->prefix('KES'),
                                TextInput::make('max_amount')->label('Maximum Fee')->numeric()->prefix('KES'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['min_amount'], fn(Builder $query, $amount): Builder => $query->where(
                            'amount',
                            '>=',
                            $amount,
                        ))->when($data['max_amount'], fn(Builder $query, $amount): Builder => $query->where(
                            'amount',
                            '<=',
                            $amount,
                        ));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min_amount'] ?? null) {
                            $indicators[] = 'Min: KES ' . number_format($data['min_amount']);
                        }
                        if ($data['max_amount'] ?? null) {
                            $indicators[] = 'Max: KES ' . number_format($data['max_amount']);
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->mutateDataUsing(function (array $data): array {
                        $data['amount'] = match ($data['type']) {
                            PRFMembershipType::FRIEND->value => 0,
                            PRFMembershipType::YEARLY_MEMBER->value => 500,
                            PRFMembershipType::LIFETIME_MEMBER->value => 5000,
                            default => 0,
                        };

                        return $data;
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Membership created')
                            ->body('New membership has been registered.')
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                Action::make('receipt_fee')
                    ->label('Receipt fee')
                    ->icon('heroicon-o-banknotes')
                    ->color(Color::Green)
                    ->schema([
                        Select::make('financial_account_ulid')
                            ->label('Received in')
                            ->options(
                                fn(): array => FinancialAccount::query()
                                    ->active()
                                    ->orderBy('name')
                                    ->pluck('name', 'ulid')
                                    ->all(),
                            )
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('ledger_category_ulid')
                            ->label('Designated for')
                            ->options(
                                fn(): array => LedgerCategory::query()
                                    ->ofKind(PRFLedgerCategoryKind::INCOME)
                                    ->where('is_active', true)
                                    ->orderBy('name')
                                    ->pluck('name', 'ulid')
                                    ->all(),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(fn(): ?string => app(ChartOfAccounts::class)->category(
                                'income.member_subscription',
                            )->ulid),

                        TextInput::make('amount')
                            ->label('Amount (KES)')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->prefix('KES')
                            ->default(fn(Membership $record): int => $record->type?->getPrice() ?? 0),

                        TextInput::make('counterparty')
                            ->label('Giver name')
                            ->maxLength(255)
                            ->default(fn(Membership $record): ?string => $record->member?->full_name),

                        TextInput::make('giver_email')
                            ->label('Giver email')
                            ->email()
                            ->default(fn(Membership $record): ?string => $record->member?->personal_email),

                        TextInput::make('giver_phone')
                            ->label('Giver phone')
                            ->default(fn(Membership $record): ?string => $record->member?->phone_number),

                        DatePicker::make('transacted_on')
                            ->label('Received on')
                            ->native(false)
                            ->default(today())
                            ->maxDate(today()),

                        TextInput::make('reference')
                            ->label('Reference')
                            ->maxLength(255)
                            ->placeholder('M-Pesa code or bank slip'),

                        Toggle::make('send_receipt')->label('Send receipt now')->default(true),
                    ])
                    ->action(function (array $data, Membership $record): void {
                        CreateJob::dispatchSync([
                            ...$data,
                            'member_ulid' => $record->member?->ulid,
                            'membership_ulid' => $record->ulid,
                            'description' => 'Membership fee (' . ($record->type?->getLabel() ?? 'Member') . ')',
                            'recorded_by' => Auth::id(),
                        ]);
                    })
                    ->successNotificationTitle('Membership fee receipted')
                    ->visible(
                        fn(Membership $record): bool => (
                            userCan(LedgerEntry::permission('create'))
                            && (!$record->approved || !(int) $record->amount)
                        ),
                    )
                    ->tooltip('Receipt the membership fee into the cashbook'),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color(Color::Green)
                    ->action(function ($record) {
                        $record->update(['approved' => true]);
                        Notification::make()->title('Membership approved')->success()->send();
                    })
                    ->visible(fn($record) => !$record->approved)
                    ->tooltip('Approve this membership'),

                EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        Notification::make()->title('Membership updated')->success()->send();
                    }),

                Action::make('view_receipt')
                    ->label('Receipt')
                    ->icon('heroicon-o-document-text')
                    ->color(Color::Blue)
                    ->action(function ($record) {
                        // Logic to generate/view receipt
                        Notification::make()
                            ->title('Receipt generated')
                            ->body('Membership receipt is being prepared.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn($record) => $record->approved && $record->amount > 0)
                    ->tooltip('Generate membership receipt'),

                DeleteAction::make()->color(Color::Red),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve_memberships')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn($record) => $record->update(['approved' => true]));

                            Notification::make()
                                ->title('Memberships approved')
                                ->body("{$count} memberships have been approved.")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('generate_receipts')
                        ->label('Generate Receipts')
                        ->icon('heroicon-o-document-text')
                        ->color(Color::Blue)
                        ->action(function ($records) {
                            $count = $records->where('approved', true)->where('amount', '>', 0)->count();

                            Notification::make()
                                ->title('Receipts generated')
                                ->body("Receipts generated for {$count} paid memberships.")
                                ->success()
                                ->send();
                        }),

                    DeleteBulkAction::make()->color(Color::Red),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn(Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
