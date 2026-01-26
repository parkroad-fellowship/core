<?php

namespace App\Filament\Resources\Payments;

use App\Enums\PRFPaymentStatus;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Models\Payment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $modelLabel = 'Payment';

    protected static ?string $pluralModelLabel = 'Payments';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Details')
                    ->description('Enter the payment information including type, member, and amount')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                StatusSchema::relationshipSelect(
                                    name: 'payment_type_id',
                                    label: 'Payment Type',
                                    relationship: 'paymentType',
                                    titleAttribute: 'name',
                                    required: true,
                                    helperText: 'Choose the category of payment (e.g., contribution, offering, tithe)',
                                )
                                    ->placeholder('e.g., Monthly Contribution')
                                    ->prefixIcon('heroicon-o-banknotes'),

                                StatusSchema::relationshipSelect(
                                    name: 'member_id',
                                    label: 'Member',
                                    relationship: 'member',
                                    titleAttribute: 'full_name',
                                    required: true,
                                    helperText: 'The person making this payment',
                                )
                                    ->placeholder('Search by name...')
                                    ->prefixIcon('heroicon-o-user'),
                            ]),

                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('amount')
                                    ->label('Payment Amount')
                                    ->helperText('Enter the amount in Kenyan Shillings')
                                    ->required()
                                    ->prefix('KES')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(1000000)
                                    ->placeholder('e.g., 5,000')
                                    ->step(0.01)
                                    ->prefixIcon('heroicon-o-banknotes'),

                                StatusSchema::enumSelect(
                                    name: 'payment_status',
                                    label: 'Payment Status',
                                    enumClass: PRFPaymentStatus::class,
                                    default: PRFPaymentStatus::PENDING->value,
                                    required: true,
                                    hiddenOnCreate: true,
                                    helperText: 'The current processing stage of this payment',
                                )
                                    ->suffixIcon('heroicon-o-check-circle'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paymentType.name')
                    ->label('Payment Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->icon('heroicon-o-banknotes')
                    ->tooltip('The category of this payment (e.g., contribution, offering)'),

                TextColumn::make('member.full_name')
                    ->label('Member Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-o-user')
                    ->tooltip('The person who made this payment'),

                TextColumn::make('amount')
                    ->label('Amount (KES)')
                    ->numeric()
                    ->money('KES', divideBy: 1)
                    ->sortable()
                    ->weight('semibold')
                    ->color(Color::Green)
                    ->icon('heroicon-o-banknotes')
                    ->tooltip('Payment amount in Kenyan Shillings'),

                TextColumn::make('payment_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        1 => 'warning',    // PENDING
                        2 => 'info',       // INITIALISED
                        3 => 'success',    // SUCCESS
                        4 => 'gray',       // CANCELLED
                        5 => 'danger',     // FAILED
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        1 => 'heroicon-o-clock',
                        2 => 'heroicon-o-arrow-path',
                        3 => 'heroicon-o-check-circle',
                        4 => 'heroicon-o-x-circle',
                        5 => 'heroicon-o-exclamation-triangle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn ($record) => PRFPaymentStatus::fromValue($record->payment_status)->getLabel())
                    ->sortable()
                    ->tooltip('Current processing stage: Pending = awaiting processing, Success = completed, Failed = unsuccessful'),

                TextColumn::make('created_at')
                    ->label('Payment Date')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->color(Color::Gray)
                    ->tooltip('Date and time when the payment was recorded'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Gray)
                    ->tooltip('When this payment record was last modified'),

                TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Red)
                    ->tooltip('Date when this payment was removed from active records'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()->visible(fn () => userCan('view payment')),
                EditAction::make()->visible(fn () => userCan('edit payment')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ])->visible(fn () => userCan('delete payment')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'view' => ViewPayment::route('/{record}'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return userCan('viewAny payment');
    }
}
