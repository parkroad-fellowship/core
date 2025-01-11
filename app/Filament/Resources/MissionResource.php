<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFMissionStatus;
use App\Filament\Resources\MissionResource\Pages;
use App\Filament\Resources\MissionResource\RelationManagers;
use App\Models\Mission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MissionResource extends Resource
{
    protected static ?string $model = Mission::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Missions Secretary';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('school_term_id')
                    ->required()
                    ->relationship('schoolTerm', 'name'),
                Forms\Components\Select::make('mission_type_id')
                    ->required()
                    ->relationship(
                        name: 'missionType',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    ),
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Select::make('school_id')
                            ->required()
                            ->relationship(
                                name: 'school',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            )
                            ->searchable(),
                        Forms\Components\TextInput::make('capacity')
                            ->label('Missionaries needed')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->required()
                            ->options(PRFMissionStatus::getOptions())
                            ->default(PRFMissionStatus::PENDING->value),

                    ])->columns(3),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\TimePicker::make('start_time')
                    ->required(),
                Forms\Components\DatePicker::make('end_date'),
                Forms\Components\TimePicker::make('end_time')
                    ->required(),
                Forms\Components\MarkdownEditor::make('executive_summary')
                    ->columnSpanFull()
                    ->visible(fn ($record) => intval($record->status) === PRFMissionStatus::SERVICED->value),
                // Only show the preparation section if the mission is not serviced
                Forms\Components\Section::make('Preparation')
                    ->schema([
                        Forms\Components\Textarea::make('mission_prep_notes')
                            ->columnSpanFull()
                            ->rows(5),
                        Forms\Components\Textarea::make('dressing_recommendations')
                            ->rows(5),
                        Forms\Components\Textarea::make(
                            'activity_recommendations'
                        )->rows(5),
                    ])->visible(fn ($record) => intval($record->status) !== PRFMissionStatus::SERVICED->value),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('schoolTerm.name'),
                Tables\Columns\TextColumn::make('school.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('missionType.name')
                    ->wrap(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        PRFMissionStatus::PENDING->value => 'Pending',
                        PRFMissionStatus::APPROVED->value => 'Approved',
                        PRFMissionStatus::REJECTED->value => 'Rejected',
                        PRFMissionStatus::CANCELLED->value => 'Cancelled',
                        PRFMissionStatus::SERVICED->value => 'Serviced',
                    ])
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('school_term_id')
                    ->label('School Term')
                    ->relationship(
                        name: 'schoolTerm',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->visible(fn () => userCan('view mission')),
                Tables\Actions\EditAction::make()->visible(fn () => userCan('edit mission')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ])->visible(fn () => userCan('delete mission')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MissionSubscriptionsRelationManager::class,
            RelationManagers\MissionExpenseRelationManager::class,
            RelationManagers\WeatherForecastsRelationManager::class,
            RelationManagers\SoulsRelationManager::class,
            RelationManagers\DebriefNotesRelationManager::class,
            RelationManagers\MissionQuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMissions::route('/'),
            'create' => Pages\CreateMission::route('/create'),
            'view' => Pages\ViewMission::route('/{record}'),
            'edit' => Pages\EditMission::route('/{record}/edit'),
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
        return userCan('viewAny mission');
    }
}
