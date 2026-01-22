<?php

namespace App\Filament\Resources\Cohorts;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\DateTimeSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Cohorts\Pages\CreateCohort;
use App\Filament\Resources\Cohorts\Pages\EditCohort;
use App\Filament\Resources\Cohorts\Pages\ListCohorts;
use App\Filament\Resources\Cohorts\Pages\ViewCohort;
use App\Filament\Resources\Cohorts\RelationManagers\CohortLettersRelationManager;
use App\Filament\Resources\Cohorts\RelationManagers\CohortMissionsRelationManager;
use App\Models\Cohort;
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

class CohortResource extends Resource
{
    protected static ?string $model = Cohort::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|\UnitEnum|null $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Cohort';

    protected static ?string $pluralModelLabel = 'Cohorts';

    protected static ?string $navigationTooltip = 'Manage student cohorts and training groups';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Cohort Information')
                    ->description('Enter the basic details about this cohort')
                    ->icon('heroicon-o-academic-cap')
                    ->collapsible()
                    ->schema([
                        ContentSchema::titleField(
                            name: 'title',
                            label: 'Cohort Title',
                            placeholder: 'e.g., Spring 2024 Cohort, Fall Training Group A',
                            helperText: 'Choose a clear title that identifies this cohort. Include the season, year, or group name for easy reference.',
                        ),

                        StatusSchema::enumSelect(
                            name: 'is_active',
                            label: 'Cohort Status',
                            enumClass: PRFActiveStatus::class,
                            default: PRFActiveStatus::ACTIVE->value,
                            helperText: 'Active cohorts can receive missions and letters. Set to Inactive when the cohort has completed training.',
                        ),
                    ])
                    ->columns(2),

                Section::make('Schedule')
                    ->description('Set the start date for this cohort')
                    ->icon('heroicon-o-calendar')
                    ->collapsible()
                    ->schema([
                        DateTimeSchema::startDateField(
                            name: 'start_date',
                            label: 'Start Date',
                            required: true,
                            autoSetEndDate: false,
                        )
                            ->helperText('Select the date when this cohort begins their training program. This helps track cohort progress and scheduling.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Cohort Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-academic-cap')
                    ->wrap(),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->color('info'),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->sortable(),

                TextColumn::make('cohort_missions_count')
                    ->label('Missions')
                    ->counts('cohortMissions')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-flag')
                    ->tooltip('Number of missions assigned to this cohort'),

                TextColumn::make('cohort_letters_count')
                    ->label('Letters')
                    ->counts('cohortLetters')
                    ->badge()
                    ->color('secondary')
                    ->icon('heroicon-o-envelope')
                    ->tooltip('Number of letters sent to this cohort'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

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

                Filter::make('recent_cohorts')
                    ->label('Recent Cohorts (Last 6 months)')
                    ->query(fn (Builder $query): Builder => $query->where('start_date', '>=', now()->subMonths(6))
                    )
                    ->toggle(),

                Filter::make('upcoming_cohorts')
                    ->label('Upcoming Cohorts')
                    ->query(fn (Builder $query): Builder => $query->where('start_date', '>', now())
                    )
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => userCan('view cohort'))
                    ->tooltip('View cohort details'),

                EditAction::make()
                    ->visible(fn () => userCan('edit cohort'))
                    ->tooltip('Edit this cohort'),

                Action::make('toggle_status')
                    ->label(fn (Cohort $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                    ->icon(fn (Cohort $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Cohort $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'warning' : 'success')
                    ->action(function (Cohort $record) {
                        $record->update([
                            'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value
                                ? PRFActiveStatus::INACTIVE->value
                                : PRFActiveStatus::ACTIVE->value,
                        ]);
                    })
                    ->tooltip('Toggle cohort status')
                    ->visible(fn () => userCan('edit cohort')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete cohort')),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete cohort')),

                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete cohort')),

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
                        ->visible(fn () => userCan('edit cohort')),

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
                        ->visible(fn () => userCan('edit cohort')),
                ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            CohortMissionsRelationManager::class,
            CohortLettersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCohorts::route('/'),
            'create' => CreateCohort::route('/create'),
            'view' => ViewCohort::route('/{record}'),
            'edit' => EditCohort::route('/{record}/edit'),
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
        return userCan('viewAny cohort');
    }
}
