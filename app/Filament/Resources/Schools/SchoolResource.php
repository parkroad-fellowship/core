<?php

namespace App\Filament\Resources\Schools;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFInstitutionType;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\SchoolSchema;
use App\Filament\Resources\Schools\Pages\CreateSchool;
use App\Filament\Resources\Schools\Pages\EditSchool;
use App\Filament\Resources\Schools\Pages\ListSchools;
use App\Filament\Resources\Schools\Pages\ViewSchool;
use App\Filament\Resources\Schools\RelationManagers\BudgetEstimatesRelationManager;
use App\Filament\Resources\Schools\RelationManagers\MissionsRelationManager;
use App\Filament\Resources\Schools\RelationManagers\SchoolContactsRelationManager;
use App\Jobs\School\CalculateRouteJob;
use App\Jobs\School\UpdateJob;
use App\Models\Mission;
use App\Models\MissionType;
use App\Models\School;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Missions Secretary';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Schools';

    protected static ?string $modelLabel = 'school';

    protected static ?string $pluralModelLabel = 'schools';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('About the school')
                ->icon('heroicon-o-academic-cap')
                ->columnSpanFull()
                ->schema([
                    SchoolSchema::nameField(),
                    SchoolSchema::duplicateWarning(),

                    Grid::make(2)
                        ->columnSpanFull()
                        ->schema([
                            SchoolSchema::institutionTypeField(),
                            SchoolSchema::studentsField(),
                        ]),

                    ToggleButtons::make('is_active')
                        ->label('Can we plan missions here?')
                        ->options([
                            PRFActiveStatus::ACTIVE->value => 'Yes, active',
                            PRFActiveStatus::INACTIVE->value => 'No, inactive',
                        ])
                        ->colors([
                            PRFActiveStatus::ACTIVE->value => 'success',
                            PRFActiveStatus::INACTIVE->value => 'gray',
                        ])
                        ->default(PRFActiveStatus::ACTIVE->value)
                        ->inline()
                        ->required()
                        ->helperText('Inactive schools stay on record but are not offered when planning a mission.')
                        ->hiddenOn('create'),

                    ContentSchema::descriptionField(
                        name: 'description',
                        label: 'Notes about the school (optional)',
                        rows: 3,
                        placeholder: 'Anything a mission team should know, e.g. the school is strict about dress code.',
                    ),

                    ContentSchema::descriptionField(
                        name: 'directions',
                        label: 'How to get there (optional)',
                        rows: 3,
                        placeholder: 'e.g. Turn left at the main roundabout; the gate is 200m on the right. Matatu route 46.',
                    ),
                ]),

            Section::make('Where is it?')
                ->icon('heroicon-o-map-pin')
                ->columnSpanFull()
                ->schema(SchoolSchema::locationFields()),

            Section::make('Advanced (optional)')
                ->description(
                    'Usual times and team size for missions here. They fill in the mission form for you. Leave empty if unsure: we use the last mission at this school instead.',
                )
                ->icon('heroicon-o-cog-6-tooth')
                ->collapsible()
                ->collapsed()
                ->columnSpanFull()
                ->schema([
                    Select::make('mission_defaults.default_mission_type_id')
                        ->label('Usual type of mission')
                        ->helperText('Also used to find a budget estimate when a mission type has none.')
                        ->options(fn(): array => self::missionTypeOptions())
                        ->searchable()
                        ->native(false)
                        ->placeholder('Pick a mission type'),

                    Repeater::make('mission_type_defaults')
                        ->label('Usual times and team size, per mission type')
                        ->addActionLabel('Add a mission type')
                        ->schema([
                            Select::make('mission_type_id')
                                ->label('Mission type')
                                ->options(fn(): array => self::missionTypeOptions())
                                ->searchable()
                                ->native(false)
                                ->required()
                                ->distinct()
                                ->columnSpan(2),

                            TimePicker::make('start_time')
                                ->label('Starts at')
                                ->seconds(false)
                                ->native(false)
                                ->format('H:i')
                                ->placeholder('e.g. 05:30'),

                            TimePicker::make('end_time')
                                ->label('Ends at')
                                ->seconds(false)
                                ->native(false)
                                ->format('H:i')
                                ->placeholder('e.g. 08:30'),

                            TextInput::make('capacity')
                                ->label('Team size')
                                ->numeric()
                                ->minValue(1)
                                ->maxValue(200)
                                ->placeholder('e.g. 3'),
                        ])
                        ->columns(2)
                        ->columnSpanFull()
                        ->reorderable(false),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('About the school')
                ->icon('heroicon-o-academic-cap')
                ->columns(3)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('name')->label('School name')->weight('semibold')->columnSpan(2),
                    TextEntry::make('is_active')
                        ->label('Status')
                        ->badge()
                        ->formatStateUsing(fn(?PRFActiveStatus $state): string => $state?->getLabel() ?? 'Active')
                        ->color(fn(?PRFActiveStatus $state): string => $state === PRFActiveStatus::INACTIVE
                            ? 'gray'
                            : 'success'),
                    TextEntry::make('institution_type')
                        ->label('Type of school')
                        ->formatStateUsing(fn(?PRFInstitutionType $state): string => $state?->getLabel() ?? 'Not set'),
                    TextEntry::make('total_students')->label('Students')->numeric(),
                    TextEntry::make('missions_count')
                        ->label('Missions so far')
                        ->state(fn(School $record): int => $record->missions()->count()),
                    TextEntry::make('description')
                        ->label('Notes')
                        ->visible(fn(School $record): bool => filled($record->description))
                        ->columnSpanFull(),
                ]),

            Section::make('Where is it?')
                ->icon('heroicon-o-map-pin')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('address')->label('Address')->columnSpanFull(),
                    TextEntry::make('google_maps')
                        ->label('Map')
                        ->state(fn(School $record): string => SchoolSchema::googleMapsUrl($record) !== null
                            ? 'Open in Google Maps'
                            : 'No map pin yet. Edit the school and find it on the map.')
                        ->url(fn(School $record): ?string => SchoolSchema::googleMapsUrl(
                            $record,
                        ), shouldOpenInNewTab: true)
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->color(fn(School $record): string => SchoolSchema::googleMapsUrl($record) !== null
                            ? 'primary'
                            : 'gray')
                        ->columnSpanFull(),
                    TextEntry::make('distance')->label('Distance from the office')->placeholder('Calculating…'),
                    TextEntry::make('static_duration')->label('Travel time')->placeholder('Calculating…'),
                    TextEntry::make('directions')
                        ->label('How to get there')
                        ->visible(fn(School $record): bool => filled($record->directions))
                        ->columnSpanFull(),
                ]),

            Section::make('People to contact')
                ->icon('heroicon-o-phone')
                ->columnSpanFull()
                ->schema([
                    RepeatableEntry::make('schoolContacts')
                        ->hiddenLabel()
                        ->placeholder('No contacts yet. Add one in the Contacts tab below.')
                        ->columns(3)
                        ->schema([
                            TextEntry::make('name')->label('Name')->weight('medium'),
                            TextEntry::make('contactType.name')->label('Role')->badge()->color('gray'),
                            TextEntry::make('phone')
                                ->label('Phone')
                                ->icon('heroicon-m-phone')
                                ->url(fn(?string $state): ?string => filled($state)
                                    ? 'tel:' . preg_replace('/\s+/', '', (string) $state)
                                    : null),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('School')
                    ->searchable(['name', 'address'])
                    ->sortable()
                    ->weight('semibold')
                    ->wrap()
                    ->description(fn(School $record): string => $record->address
                        ? Str::limit($record->address, 60)
                        : 'No address yet'),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn(?PRFActiveStatus $state): string => $state?->getLabel() ?? 'Active')
                    ->color(fn(?PRFActiveStatus $state): string => $state === PRFActiveStatus::INACTIVE
                        ? 'gray'
                        : 'success'),

                TextColumn::make('institution_type')
                    ->label('Type')
                    ->formatStateUsing(fn(?PRFInstitutionType $state): string => $state?->getLabel() ?? '')
                    ->toggleable(),

                TextColumn::make('total_students')->label('Students')->numeric()->sortable()->toggleable(),

                TextColumn::make('missions_count')->label('Missions')->counts('missions')->sortable(),

                TextColumn::make('static_duration')->label('Travel time')->placeholder('Calculating…')->toggleable(),

                TextColumn::make('created_at')
                    ->label('Added on')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Deleted on')
                    ->date('j M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Active and inactive')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active only',
                        PRFActiveStatus::INACTIVE->value => 'Inactive only',
                    ]),

                SelectFilter::make('institution_type')
                    ->label('Type of school')
                    ->options(SchoolSchema::institutionTypeOptions()),

                Filter::make('no_missions')
                    ->label('No missions yet')
                    ->query(fn(Builder $query): Builder => $query->doesntHave('missions')),

                TrashedFilter::make()
                    ->label('Deleted schools')
                    ->placeholder('Hide deleted')
                    ->trueLabel('Show deleted too')
                    ->falseLabel('Only deleted'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordUrl(fn(School $record): ?string => (
                userCan(School::permission('view')) ? self::getUrl('view', ['record' => $record]) : null
            ))
            ->recordActions([
                Action::make('planMission')
                    ->label('Plan a mission')
                    ->icon('heroicon-m-calendar-days')
                    ->button()
                    ->size('sm')
                    ->url(fn(School $record): string => SchoolSchema::planMissionUrl($record))
                    ->visible(
                        fn(School $record): bool => !$record->trashed() && userCan(Mission::permission('create')),
                    ),

                ActionGroup::make([
                    EditAction::make()->visible(fn(): bool => userCan(School::permission('edit'))),

                    Action::make('calculate_distance')
                        ->label('Work out distance again')
                        ->icon('heroicon-m-arrow-path')
                        ->action(fn(School $record) => self::recalculateDistance($record))
                        ->visible(fn(): bool => userCan(School::permission('edit'))),

                    Action::make('toggle_status')
                        ->label(fn(School $record): string => !SchoolSchema::isActive($record)
                            ? 'Make active'
                            : 'Make inactive')
                        ->icon(fn(School $record): string => !SchoolSchema::isActive($record)
                            ? 'heroicon-m-check-circle'
                            : 'heroicon-m-pause-circle')
                        ->requiresConfirmation()
                        ->modalDescription(fn(School $record): string => !SchoolSchema::isActive($record)
                            ? 'The school will be offered again when planning missions.'
                            : 'The school stays on record but is no longer offered when planning missions.')
                        ->action(function (School $record): void {
                            $status = !SchoolSchema::isActive($record)
                                ? PRFActiveStatus::ACTIVE
                                : PRFActiveStatus::INACTIVE;

                            UpdateJob::dispatchSync(['is_active' => $status], $record->ulid);

                            Notification::make()
                                ->success()
                                ->title("{$record->name} is now {$status->getLabel()}")
                                ->send();
                        })
                        ->visible(
                            fn(School $record): bool => !$record->trashed() && userCan(School::permission('edit')),
                        ),

                    DeleteAction::make()->visible(fn(): bool => userCan(School::permission('delete'))),

                    RestoreAction::make()->visible(fn(): bool => userCan(School::permission('restore'))),
                ])
                    ->label('More')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('calculate_distances')
                        ->label('Work out distances again')
                        ->icon('heroicon-m-arrow-path')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                if ($record instanceof School) {
                                    CalculateRouteJob::dispatch($record);
                                }
                            }

                            Notification::make()
                                ->info()
                                ->title('Working out distances')
                                ->body('Travel times will appear in a minute or two.')
                                ->send();
                        })
                        ->visible(fn(): bool => userCan(School::permission('edit'))),

                    BulkAction::make('activate_schools')
                        ->label('Make active')
                        ->icon('heroicon-m-check-circle')
                        ->action(fn(Collection $records) => self::setStatus($records, PRFActiveStatus::ACTIVE))
                        ->visible(fn(): bool => userCan(School::permission('edit'))),

                    BulkAction::make('deactivate_schools')
                        ->label('Make inactive')
                        ->icon('heroicon-m-pause-circle')
                        ->requiresConfirmation()
                        ->action(fn(Collection $records) => self::setStatus($records, PRFActiveStatus::INACTIVE))
                        ->visible(fn(): bool => userCan(School::permission('edit'))),

                    DeleteBulkAction::make()->visible(fn(): bool => userCan(School::permission('delete'))),

                    ForceDeleteBulkAction::make()->visible(fn(): bool => userCan(School::permission('forceDelete'))),

                    RestoreBulkAction::make()->visible(fn(): bool => userCan(School::permission('restore'))),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->searchPlaceholder('Search by name or address')
            ->emptyStateHeading('No schools found')
            ->emptyStateDescription('Try a different search, or add the school.')
            ->emptyStateIcon('heroicon-o-academic-cap');
    }

    public static function recalculateDistance(School $school): void
    {
        CalculateRouteJob::dispatch($school);

        Notification::make()
            ->info()
            ->title('Working out the distance')
            ->body('The distance and travel time will update in a minute or two. Refresh the page to see them.')
            ->send();
    }

    /**
     * @param  Collection<int, School>  $records
     */
    private static function setStatus(Collection $records, PRFActiveStatus $status): void
    {
        $records->each(fn(School $record) => UpdateJob::dispatchSync(['is_active' => $status], $record->ulid));

        Notification::make()->success()->title("{$records->count()} schools are now {$status->getLabel()}")->send();
    }

    /**
     * @return array<int, string>
     */
    private static function missionTypeOptions(): array
    {
        /** @var array<int, string> */
        return MissionType::query()
            ->where('is_active', PRFActiveStatus::ACTIVE)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'address'];
    }

    /**
     * @return array<string, string>
     */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return (
            $record instanceof School && filled($record->address) ? ['Address' => Str::limit($record->address, 60)] : []
        );
    }

    public static function getGlobalSearchResultUrl(Model $record): ?string
    {
        return self::getUrl('view', ['record' => $record]);
    }

    public static function getRelations(): array
    {
        return [
            MissionsRelationManager::class,
            SchoolContactsRelationManager::class,
            BudgetEstimatesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchools::route('/'),
            'create' => CreateSchool::route('/create'),
            'view' => ViewSchool::route('/{record}'),
            'edit' => EditSchool::route('/{record}/edit'),
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
        return userCan(School::permission('viewAny'));
    }

    /**
     * Convert the stored mission_defaults JSON into repeater rows for the form.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function missionDefaultsToRows(School $school): array
    {
        $defaults = is_array($school->mission_defaults) ? $school->mission_defaults : [];
        $types = $defaults['types'] ?? [];

        return collect($types)
            ->map(fn(array $typeDefaults, string $missionTypeId) => [
                'mission_type_id' => (int) $missionTypeId,
                'start_time' => $typeDefaults['start_time'] ?? null,
                'end_time' => $typeDefaults['end_time'] ?? null,
                'capacity' => isset($typeDefaults['capacity']) ? (int) $typeDefaults['capacity'] : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Convert repeater rows from the form into the mission_defaults JSON shape.
     *
     * @return array{types: array<string, array<string, mixed>>, default_mission_type_id?: int}
     */
    public static function rowsToMissionDefaults(array $rows, int|string|null $defaultMissionTypeId): array
    {
        $types = [];

        foreach ($rows as $row) {
            if (empty($row['mission_type_id'])) {
                continue;
            }

            $entry = array_filter(
                [
                    'start_time' => $row['start_time'] ?? null,
                    'end_time' => $row['end_time'] ?? null,
                    'capacity' => filled($row['capacity'] ?? null) ? (int) $row['capacity'] : null,
                ],
                fn($value) => filled($value),
            );

            if (filled($entry)) {
                $types[(string) $row['mission_type_id']] = $entry;
            }
        }

        $payload = ['types' => $types];

        if ($defaultMissionTypeId !== null && $defaultMissionTypeId !== '') {
            $payload['default_mission_type_id'] = (int) $defaultMissionTypeId;
        }

        return $payload;
    }
}
