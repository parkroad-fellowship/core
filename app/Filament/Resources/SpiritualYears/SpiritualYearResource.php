<?php

namespace App\Filament\Resources\SpiritualYears;

use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Resources\SpiritualYears\Pages\CreateSpiritualYear;
use App\Filament\Resources\SpiritualYears\Pages\EditSpiritualYear;
use App\Filament\Resources\SpiritualYears\Pages\ListSpiritualYears;
use App\Filament\Resources\SpiritualYears\Pages\ViewSpiritualYear;
use App\Models\SpiritualYear;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SpiritualYearResource extends Resource
{
    protected static ?string $model = SpiritualYear::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Spiritual Year';

    protected static ?string $pluralModelLabel = 'Spiritual Years';

    protected static ?string $navigationTooltip = 'Manage spiritual calendar years and periods';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Spiritual Year Information')
                    ->description('Define spiritual calendar years for organizing activities and events')
                    ->icon('heroicon-o-calendar')
                    ->schema([
                        ContentSchema::nameField(
                            name: 'name',
                            label: 'Year Name',
                            placeholder: 'e.g., 2024, Year of Faith, Jubilee Year',
                            helperText: 'The name or label for this spiritual year used for organizing events and reports',
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
                    ->label('Spiritual Year')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-calendar')
                    ->tooltip('Spiritual calendar year'),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date this year was created'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Last modification date'),

                TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date this year was deleted'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Show Deleted')
                    ->placeholder('Active years only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),
            ])
            ->recordActions([
                ViewAction::make()->visible(fn () => userCan('view spiritual year')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ])->visible(fn () => userCan('delete spiritual year')),
            ])
            ->defaultSort('name', 'desc')
            ->searchPlaceholder('Search spiritual years...')
            ->emptyStateHeading('No spiritual years found')
            ->emptyStateDescription('Start by adding your first spiritual year to the system.')
            ->emptyStateIcon('heroicon-o-calendar');
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
            'index' => ListSpiritualYears::route('/'),
            'create' => CreateSpiritualYear::route('/create'),
            'view' => ViewSpiritualYear::route('/{record}'),
            'edit' => EditSpiritualYear::route('/{record}/edit'),
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
        return userCan('viewAny spiritual year');
    }
}
