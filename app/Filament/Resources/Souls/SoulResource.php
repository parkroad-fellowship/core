<?php

namespace App\Filament\Resources\Souls;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFSoulDecisionType;
use App\Filament\Exports\SoulExporter;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Souls\Pages\CreateSoul;
use App\Filament\Resources\Souls\Pages\EditSoul;
use App\Filament\Resources\Souls\Pages\ListSouls;
use App\Filament\Resources\Souls\Pages\ViewSoul;
use App\Models\Soul;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
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

class SoulResource extends Resource
{
    protected static ?string $model = Soul::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|\UnitEnum|null $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Soul';

    protected static ?string $pluralModelLabel = 'Souls';

    protected static ?string $navigationLabel = 'Souls';

    protected static ?string $navigationTooltip = 'Manage student souls won during missions';

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static int $globalSearchResultsLimit = 20;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'success' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = static::getNavigationBadge();

        return $count.' soul'.($count !== 1 ? 's' : '').' won for Christ';
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->full_name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'School' => $record->mission?->school?->name ?? 'N/A',
            'Class' => $record->classGroup?->name ?? 'N/A',
            'Admission Number' => $record->admission_number ?? 'N/A',
            'Won On' => $record->created_at->format('M j, Y'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'admission_number', 'mission.school.name', 'classGroup.name'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Student Information')
                    ->description('Record details of students who made decisions during missions. This information helps with follow-up and discipleship.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                ContentSchema::nameField(
                                    name: 'full_name',
                                    label: 'Full Name',
                                    placeholder: 'e.g., John Doe Mwangi',
                                    required: true,
                                    helperText: 'Enter the student\'s complete name as they provided it. This will be used for follow-up communications.',
                                ),

                                TextInput::make('admission_number')
                                    ->label('Admission Number')
                                    ->helperText('The student\'s school admission or registration number. This helps identify them within their institution.')
                                    ->maxLength(255)
                                    ->placeholder('e.g., ADM/2024/001'),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Class & Decision Details')
                    ->description('Specify which class the student belongs to and the type of spiritual decision they made.')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                StatusSchema::relationshipSelect(
                                    name: 'class_group_id',
                                    label: 'Class Group',
                                    relationship: 'classGroup',
                                    titleAttribute: 'name',
                                    required: true,
                                    modifyQuery: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                                    helperText: 'Select the class or grade level this student belongs to. Only active class groups are shown.',
                                ),

                                StatusSchema::enumSelect(
                                    name: 'decision_type',
                                    label: 'Decision Type',
                                    enumClass: PRFSoulDecisionType::class,
                                    default: PRFSoulDecisionType::SALVATION,
                                    required: true,
                                    hiddenOnCreate: false,
                                    helperText: 'Choose the type of spiritual decision the student made during the mission.',
                                ),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Additional Notes')
                    ->description('Any extra information that may be helpful for follow-up.')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        ContentSchema::notesField(
                            name: 'notes',
                            label: 'Notes',
                            rows: 4,
                            required: false,
                            placeholder: 'e.g., Student expressed interest in joining a Bible study group...',
                        )->helperText('Record any additional observations, prayer requests, or follow-up needs for this student.'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mission.school.name')
                    ->label('School')
                    ->icon('heroicon-o-academic-cap')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->tooltip(fn ($record) => $record->mission?->school?->name ?? 'No school recorded'),

                TextColumn::make('classGroup.name')
                    ->label('Class')
                    ->icon('heroicon-o-user-group')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('full_name')
                    ->label('Student Name')
                    ->icon('heroicon-o-user')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('admission_number')
                    ->label('Admission No.')
                    ->icon('heroicon-o-identification')
                    ->searchable()
                    ->placeholder('Not provided')
                    ->toggleable(),

                TextColumn::make('decision_type')
                    ->label('Decision Type')
                    ->formatStateUsing(fn ($record) => PRFSoulDecisionType::fromValue($record->decision_type)->getLabel())
                    ->badge()
                    ->color(fn ($record) => PRFSoulDecisionType::fromValue($record->decision_type)->getColor())
                    ->sortable()
                    ->tooltip(fn ($record) => $record->notes),

                TextColumn::make('mission.start_date')
                    ->label('Mission Date')
                    ->date('M j, Y')
                    ->icon('heroicon-o-calendar-days')
                    ->sortable()
                    ->tooltip(fn ($record) => 'Mission: '.$record->mission?->start_date?->format('F j, Y')),

                TextColumn::make('created_at')
                    ->label('Soul Won On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-o-heart')
                    ->color('success')
                    ->tooltip(fn ($record) => 'Soul won: '.$record->created_at->format('F j, Y \a\t g:i A')),

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
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                SelectFilter::make('mission.school_id')
                    ->label('School')
                    ->relationship('mission.school', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Schools'),

                SelectFilter::make('class_group_id')
                    ->label('Class Group')
                    ->relationship('classGroup', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Classes'),

                Filter::make('mission_date')
                    ->schema([
                        DatePicker::make('from')
                            ->native(false)
                            ->label('From Date'),
                        DatePicker::make('until')
                            ->native(false)
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereHas('mission', fn ($query) => $query->whereDate('start_date', '>=', $date)),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereHas('mission', fn ($query) => $query->whereDate('start_date', '<=', $date)),
                            );
                    }),

                Filter::make('has_admission_number')
                    ->label('Has Admission Number')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('admission_number'))
                    ->toggle(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view soul')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit soul')),
                    Action::make('view_mission')
                        ->label('View Mission')
                        ->icon('heroicon-o-map-pin')
                        ->color('primary')
                        ->url(fn ($record) => $record->mission ? route('filament.admin.resources.missions.view', $record->mission) : null)
                        ->visible(fn ($record) => $record->mission && userCan('view mission')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete soul')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete soul')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete soul')),
                ])->visible(fn () => userCan('delete soul')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Export Souls')
                    ->icon('heroicon-m-inbox-arrow-down')
                    ->exporter(SoulExporter::class)
                    ->modifyQueryUsing(fn (Builder $query) => $query
                        ->orderBy('created_at', 'desc')
                        ->withoutGlobalScopes([
                            SoftDeletingScope::class,
                        ])),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => ListSouls::route('/'),
            'create' => CreateSoul::route('/create'),
            'view' => ViewSoul::route('/{record}'),
            'edit' => EditSoul::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return userCan('viewAny soul');
    }
}
