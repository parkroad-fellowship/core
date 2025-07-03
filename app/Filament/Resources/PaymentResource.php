<?php

namespace App\Filament\Resources;

use App\Enums\PRFPaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
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

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = '💰 Finance Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $modelLabel = 'Payment';

    protected static ?string $pluralModelLabel = 'Payments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('💳 Payment Information')
                    ->description('Payment transaction details and member information')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('payment_type_id')
                                    ->label('💳 Payment Type')
                                    ->helperText('Select the type of payment')
                                    ->required()
                                    ->relationship('paymentType', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-banknotes'),

                                Forms\Components\Select::make('member_id')
                                    ->label('👤 Member')
                                    ->helperText('Select the member making the payment')
                                    ->required()
                                    ->relationship('member', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-user'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
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

                                Forms\Components\Select::make('payment_status')
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
                Tables\Columns\TextColumn::make('paymentType.name')
                    ->label('💳 Payment Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->icon('heroicon-o-banknotes')
                    ->tooltip('Type of payment transaction'),

                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('👤 Member')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-o-user')
                    ->tooltip('Member making the payment'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('💰 Amount')
                    ->numeric()
                    ->money('KES', divideBy: 1)
                    ->sortable()
                    ->weight('semibold')
                    ->color(Color::Green)
                    ->icon('heroicon-o-banknotes')
                    ->tooltip('Payment amount in KES'),

                Tables\Columns\TextColumn::make('payment_status')
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

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Payment Date')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->color(Color::Gray)
                    ->tooltip('Date payment was initiated'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Gray)
                    ->tooltip('Last status update'),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('🗑️ Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Red)
                    ->tooltip('Date payment was deleted'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(fn () => userCan('view payment')),
                Tables\Actions\EditAction::make()->visible(fn () => userCan('edit payment')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
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
