<?php

namespace App\Filament\Resources\Gifts;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Gifts\Pages\CreateGift;
use App\Filament\Resources\Gifts\Pages\EditGift;
use App\Filament\Resources\Gifts\Pages\ListGifts;
use App\Filament\Resources\Gifts\Pages\ViewGift;
use App\Models\Gift;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class GiftResource extends Resource
{
    protected static ?string $model = Gift::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-gift';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $label = 'Gifts & Talents';

    protected static ?string $pluralModelLabel = 'Gifts & Talents';

    protected static ?string $navigationTooltip = 'Manage spiritual gifts and talents';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Gift & Talent Information')
                    ->description('Define spiritual gifts and talents that members can possess. These help identify and organize member abilities for ministry placement.')
                    ->icon('heroicon-o-gift')
                    ->schema([
                        ContentSchema::nameField(
                            name: 'name',
                            label: 'Gift/Talent Name',
                            placeholder: 'e.g., Teaching, Music, Leadership, Hospitality',
                            required: true,
                            helperText: 'Enter a descriptive name for this spiritual gift or talent. This will be displayed when assigning gifts to members.',
                        ),

                        StatusSchema::enumSelect(
                            name: 'is_active',
                            label: 'Status',
                            enumClass: PRFActiveStatus::class,
                            default: PRFActiveStatus::ACTIVE->value,
                            required: true,
                            hiddenOnCreate: true,
                            helperText: 'Active gifts can be assigned to members. Inactive gifts are hidden from selection but preserved for existing records.',
                        ),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Gift/Talent Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-gift')
                    ->wrap(),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->sortable(),

                TextColumn::make('members_count')
                    ->label('Members')
                    ->counts('members')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-users')
                    ->tooltip('Number of members with this gift/talent'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->native(false),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->native(false),

                Filter::make('popular_gifts')
                    ->label('Popular Gifts (5+ Members)')
                    ->query(fn (Builder $query): Builder => $query->withCount('members')->having('members_count', '>=', 5)
                    )
                    ->toggle(),

                Filter::make('unused_gifts')
                    ->label('Unused Gifts')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('members')
                    )
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => userCan('view gift'))
                    ->tooltip('View gift/talent details'),

                EditAction::make()
                    ->visible(fn () => userCan('edit gift'))
                    ->tooltip('Edit this gift/talent'),

                Action::make('toggle_status')
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete gift')),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete gift')),

                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete gift')),

                    BulkAction::make('bulk_activate')
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

                    BulkAction::make('bulk_deactivate')
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
            'index' => ListGifts::route('/'),
            'create' => CreateGift::route('/create'),
            'view' => ViewGift::route('/{record}'),
            'edit' => EditGift::route('/{record}/edit'),
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
