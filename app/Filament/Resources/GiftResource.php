<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\GiftResource\Pages;
use App\Models\Gift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GiftResource extends Resource
{
    protected static ?string $model = Gift::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $label = 'Gifts & Talents';

    protected static ?string $pluralModelLabel = 'Gifts & Talents';

    protected static ?string $navigationTooltip = 'Manage spiritual gifts and talents';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Gift & Talent Information')
                    ->description('Define spiritual gifts and talents')
                    ->icon('heroicon-o-gift')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Gift/Talent Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter the name of the spiritual gift or talent')
                            ->placeholder('e.g., Teaching, Music, Leadership'),

                        Forms\Components\Select::make('is_active')
                            ->label('Status')
                            ->required()
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set the current status of this gift/talent')
                            ->hiddenOn('create'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Gift/Talent Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-gift')
                    ->wrap(),

                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-users')
                    ->tooltip('Number of members with this gift/talent'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->native(false),

                Tables\Filters\Filter::make('popular_gifts')
                    ->label('Popular Gifts (5+ Members)')
                    ->query(fn (Builder $query): Builder => $query->withCount('members')->having('members_count', '>=', 5)
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('unused_gifts')
                    ->label('Unused Gifts')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('members')
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => userCan('view gift'))
                    ->tooltip('View gift/talent details'),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => userCan('edit gift'))
                    ->tooltip('Edit this gift/talent'),

                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Gift $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                    ->icon(fn (Gift $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Gift $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'warning' : 'success')
                    ->action(function (Gift $record) {
                        $record->update([
                            'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value
                                ? PRFActiveStatus::INACTIVE->value
                                : PRFActiveStatus::ACTIVE->value,
                        ]);
                    })
                    ->tooltip('Toggle gift/talent status')
                    ->visible(fn () => userCan('edit gift')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete gift')),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete gift')),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete gift')),

                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit gift')),

                    Tables\Actions\BulkAction::make('bulk_deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit gift')),
                ]),
            ])
            ->defaultSort('name')
            ->striped();
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
            'index' => Pages\ListGifts::route('/'),
            'create' => Pages\CreateGift::route('/create'),
            'view' => Pages\ViewGift::route('/{record}'),
            'edit' => Pages\EditGift::route('/{record}/edit'),
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
        return userCan('viewAny gift');
    }
}
