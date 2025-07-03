<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\CohortResource\Pages;
use App\Filament\Resources\CohortResource\RelationManagers;
use App\Models\Cohort;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class CohortResource extends Resource
{
    protected static ?string $model = Cohort::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Cohort';

    protected static ?string $pluralModelLabel = 'Cohorts';

    protected static ?string $navigationTooltip = 'Manage student cohorts and training groups';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cohort Information')
                    ->description('Define the cohort details and schedule')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Cohort Title')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter a descriptive title for this cohort')
                            ->placeholder('e.g., Spring 2024 Cohort'),
                        
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->timezone(Auth::user()->timezone ?? 'UTC')
                            ->native(false)
                            ->required()
                            ->helperText('When does this cohort begin?')
                            ->displayFormat('M j, Y')
                            ->closeOnDateSelection(),
                        
                        Forms\Components\Select::make('is_active')
                            ->label('Status')
                            ->required()
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set the current status of this cohort')
                            ->hiddenOn('create'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Cohort Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-academic-cap')
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('M j, Y')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('cohort_missions_count')
                    ->label('Missions')
                    ->counts('cohortMissions')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-flag')
                    ->tooltip('Number of missions assigned to this cohort'),
                
                Tables\Columns\TextColumn::make('cohort_letters_count')
                    ->label('Letters')
                    ->counts('cohortLetters')
                    ->badge()
                    ->color('secondary')
                    ->icon('heroicon-o-envelope')
                    ->tooltip('Number of letters sent to this cohort'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                
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
                
                Tables\Filters\Filter::make('recent_cohorts')
                    ->label('Recent Cohorts (Last 6 months)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('start_date', '>=', now()->subMonths(6))
                    )
                    ->toggle(),
                
                Tables\Filters\Filter::make('upcoming_cohorts')
                    ->label('Upcoming Cohorts')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('start_date', '>', now())
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => userCan('view cohort'))
                    ->tooltip('View cohort details'),
                
                Tables\Actions\EditAction::make()
                    ->visible(fn () => userCan('edit cohort'))
                    ->tooltip('Edit this cohort'),
                
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (Cohort $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                    ->icon(fn (Cohort $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (Cohort $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'warning' : 'success')
                    ->action(function (Cohort $record) {
                        $record->update([
                            'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value 
                                ? PRFActiveStatus::INACTIVE->value 
                                : PRFActiveStatus::ACTIVE->value
                        ]);
                    })
                    ->tooltip('Toggle cohort status')
                    ->visible(fn () => userCan('edit cohort')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete cohort')),
                    
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete cohort')),
                    
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete cohort')),
                    
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
                        ->visible(fn () => userCan('edit cohort')),
                    
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
                        ->visible(fn () => userCan('edit cohort')),
                ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CohortMissionsRelationManager::class,
            RelationManagers\CohortLettersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCohorts::route('/'),
            'create' => Pages\CreateCohort::route('/create'),
            'view' => Pages\ViewCohort::route('/{record}'),
            'edit' => Pages\EditCohort::route('/{record}/edit'),
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
