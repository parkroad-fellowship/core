<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Filament\Resources\PaymentResource\Pages\CreatePayment;
use App\Filament\Resources\PaymentResource\Pages\ViewPayment;
use App\Filament\Resources\PaymentResource\Pages\EditPayment;
use App\Enums\PRFPaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-credit-card';

    protected static string | \UnitEnum | null $navigationGroup = 'Treasurer';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $modelLabel = 'Payment';

    protected static ?string $pluralModelLabel = 'Payments';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('💳 Payment Information')
                    ->description('Payment transaction details and member information')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('payment_type_id')
                                    ->label('💳 Payment Type')
                                    ->helperText('Select the type of payment')
                                    ->required()
                                    ->relationship('paymentType', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-banknotes'),

                                Select::make('member_id')
                                    ->label('👤 Member')
                                    ->helperText('Select the member making the payment')
                                    ->required()
                                    ->relationship('member', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-user'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('amount')
                                    ->label('💰 Amount')
                                    ->helperText('Payment amount in Kenyan Shillings')
                                    ->required()
                                    ->prefix('KES')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(1000000)
                                    ->placeholder('0.00')
                                    ->step(0.01)
                                    ->prefixIcon('heroicon-o-banknotes'),

                                Select::make('payment_status')
                                    ->label('📊 Payment Status')
                                    ->helperText('Current status of the payment')
                                    ->required()
                                    ->options(PRFPaymentStatus::getOptions())
                                    ->default(PRFPaymentStatus::PENDING->value)
                                    ->hiddenOn('create')
                                    ->native(false)
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
                    ->label('💳 Payment Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->icon('heroicon-o-banknotes')
                    ->tooltip('Type of payment transaction'),

                TextColumn::make('member.full_name')
                    ->label('👤 Member')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-o-user')
                    ->tooltip('Member making the payment'),

                TextColumn::make('amount')
                    ->label('💰 Amount')
                    ->numeric()
                    ->money('KES', divideBy: 1)
                    ->sortable()
                    ->weight('semibold')
                    ->color(Color::Green)
                    ->icon('heroicon-o-banknotes')
                    ->tooltip('Payment amount in KES'),

                TextColumn::make('payment_status')
                    ->label('📊 Status')
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
                    ->tooltip('Payment processing status'),

                TextColumn::make('created_at')
                    ->label('📅 Payment Date')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->color(Color::Gray)
                    ->tooltip('Date payment was initiated'),

                TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Gray)
                    ->tooltip('Last status update'),

                TextColumn::make('deleted_at')
                    ->label('🗑️ Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Red)
                    ->tooltip('Date payment was deleted'),
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
