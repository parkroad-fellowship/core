<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\PaymentTypeResource\Pages;
use App\Models\PaymentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PaymentTypeResource extends Resource
{
    protected static ?string $model = PaymentType::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Treasurer';

    protected static ?string $modelLabel = 'Payment Type';

    protected static ?string $pluralModelLabel = 'Payment Types';

    protected static ?string $navigationTooltip = 'Manage different types of payments';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Type Information')
                    ->description('Define the payment type details')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Payment Type Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter a clear name for this payment type')
                            ->placeholder('e.g., Membership Fee, Tithe, Offering'),

                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Provide a brief description of this payment type')
                            ->placeholder('Brief description of what this payment is for'),

                        Forms\Components\Select::make('is_active')
                            ->label('Status')
                            ->required()
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set the current status of this payment type')
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Payment Type')
                    ->icon('heroicon-o-credit-card')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),

                Tables\Columns\TextColumn::make('payments_count')
                    ->label('Payments Count')
                    ->counts('payments')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-banknotes')
                    ->tooltip('Number of payments using this type'),

                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFActiveStatus::fromValue($state)->getLabel())
                    ->color(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Created: '.$record->created_at->format('F j, Y \a\t g:i A')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->placeholder('All Statuses'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view payment type')),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit payment type')),
                    Tables\Actions\Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->update([
                                'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value ? PRFActiveStatus::INACTIVE->value : PRFActiveStatus::ACTIVE->value,
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit payment type')),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete payment type')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete payment type')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete payment type')),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit payment type')),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit payment type')),
                ])->visible(fn () => userCan('delete payment type')),
            ])
            ->defaultSort('name', 'asc');
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
            'index' => Pages\ListPaymentTypes::route('/'),
            'create' => Pages\CreatePaymentType::route('/create'),
            'view' => Pages\ViewPaymentType::route('/{record}'),
            'edit' => Pages\EditPaymentType::route('/{record}/edit'),
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
        return userCan('viewAny payment type');
    }
}
