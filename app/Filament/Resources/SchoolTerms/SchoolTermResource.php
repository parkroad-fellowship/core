<?php

namespace App\Filament\Resources\SchoolTerms;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\SchoolTerms\Pages\CreateSchoolTerm;
use App\Filament\Resources\SchoolTerms\Pages\EditSchoolTerm;
use App\Filament\Resources\SchoolTerms\Pages\ListSchoolTerms;
use App\Filament\Resources\SchoolTerms\Pages\ViewSchoolTerm;
use App\Filament\Resources\SchoolTerms\RelationManagers\MissionsRelationManager;
use App\Models\SchoolTerm;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SchoolTermResource extends Resource
{
    protected static ?string $model = SchoolTerm::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|\UnitEnum|null $navigationGroup = 'Missions Secretary';

    protected static ?string $modelLabel = 'School Term';

    protected static ?string $pluralModelLabel = 'School Terms';

    protected static ?string $navigationLabel = 'School Terms';

    protected static ?string $navigationTooltip = 'Manage academic terms and periods';

    protected static ?string $recordTitleAttribute = 'name';

    protected static int $globalSearchResultsLimit = 20;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_active', PRFActiveStatus::ACTIVE->value)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'success' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = static::getNavigationBadge();

        return $count.' active school term'.($count !== 1 ? 's' : '');
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name.' ('.$record->year.')';
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Year' => $record->year,
            'Status' => PRFActiveStatus::fromValue($record->is_active)->getLabel(),
            'Missions' => $record->missions_count ?? 0,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'year'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('School Term Information')
                    ->description('Define academic terms and periods for organizing missions')
                    ->icon('heroicon-o-calendar-days')
                    ->schema([
                        ContentSchema::nameField(
                            name: 'name',
                            label: 'Term Name',
                            placeholder: 'e.g., Term 1, First Term, Q1, Spring Semester',
                            helperText: 'The name of the academic term used to organize missions',
                        ),

                        TextInput::make('year')
                            ->label('Academic Year')
                            ->required()
                            ->numeric()
                            ->minValue(2020)
                            ->maxValue(2050)
                            ->default(date('Y'))
                            ->placeholder('e.g., 2024')
                            ->helperText('The calendar year this term belongs to'),

                        StatusSchema::enumSelect(
                            name: 'is_active',
                            label: 'Status',
                            enumClass: PRFActiveStatus::class,
                            default: PRFActiveStatus::ACTIVE->value,
                            helperText: 'Active terms can have missions scheduled',
                        ),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Term Name')
                    ->icon('heroicon-o-calendar-days')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->tooltip('Academic term name'),

                TextColumn::make('year')
                    ->label('Academic Year')
                    ->icon('heroicon-o-calendar')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->tooltip('Calendar year'),

                TextColumn::make('missions_count')
                    ->label('Missions')
                    ->counts('missions')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-map-pin')
                    ->tooltip('Number of missions in this term'),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFActiveStatus::fromValue($state)->getLabel())
                    ->color(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable()
                    ->tooltip(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'Term is active' : 'Term is inactive'),

                TextColumn::make('term_period')
                    ->label('Term Period')
                    ->getStateUsing(fn ($record) => $record->name.' '.$record->year)
                    ->icon('heroicon-o-academic-cap')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Full term period label'),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-o-clock')
                    ->tooltip(fn ($record) => 'Created: '.$record->created_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date term was deleted'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->placeholder('All Statuses'),

                SelectFilter::make('year')
                    ->label('Academic Year')
                    ->options(function () {
                        $currentYear = date('Y');
                        $years = [];
                        for ($i = $currentYear - 5; $i <= $currentYear + 2; $i++) {
                            $years[$i] = $i;
                        }

                        return $years;
                    })
                    ->placeholder('All Years'),

                Filter::make('has_missions')
                    ->label('Has Missions')
                    ->query(fn (Builder $query): Builder => $query->has('missions'))
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view school term')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit school term')),
                    Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->update([
                                'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value ? PRFActiveStatus::INACTIVE->value : PRFActiveStatus::ACTIVE->value,
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit school term')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete school term')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete school term')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete school term')),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit school term')),
                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit school term')),
                ])->visible(fn () => userCan('delete school term')),
            ])
            ->defaultSort('year', 'desc')
            ->searchPlaceholder('Search school terms...')
            ->emptyStateHeading('No school terms found')
            ->emptyStateDescription('Start by adding your first school term to the system.')
            ->emptyStateIcon('heroicon-o-calendar-days');
    }

    public static function getRelations(): array
    {
        return [
            MissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchoolTerms::route('/'),
            'create' => CreateSchoolTerm::route('/create'),
            'view' => ViewSchoolTerm::route('/{record}'),
            'edit' => EditSchoolTerm::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('year', 'desc')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return userCan('viewAny school term');
    }
}
