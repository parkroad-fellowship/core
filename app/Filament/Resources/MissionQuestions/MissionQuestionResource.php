<?php

namespace App\Filament\Resources\MissionQuestions;

use App\Enums\PRFActiveStatus;
use App\Filament\Exports\MissionQuestionExporter;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\MissionQuestions\Pages\CreateMissionQuestion;
use App\Filament\Resources\MissionQuestions\Pages\EditMissionQuestion;
use App\Filament\Resources\MissionQuestions\Pages\ListMissionQuestions;
use App\Filament\Resources\MissionQuestions\Pages\ViewMissionQuestion;
use App\Models\MissionQuestion;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionQuestionResource extends Resource
{
    protected static ?string $model = MissionQuestion::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Mission Question';

    protected static ?string $pluralModelLabel = 'Mission Questions';

    protected static ?string $navigationTooltip = 'Manage questions asked during missions';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Mission Information')
                    ->description('Link this question to the mission where it was asked. This helps track questions by location and event.')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        StatusSchema::relationshipSelect(
                            name: 'mission_id',
                            label: 'Mission (School)',
                            relationship: 'mission.school',
                            titleAttribute: 'name',
                            required: true,
                            searchable: true,
                            preload: true,
                            modifyQuery: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            helperText: 'Select the mission or school where this question was asked. You can search by school name to find the right mission.',
                        )->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Question Details')
                    ->description('Record the question exactly as it was asked during the mission. This information is valuable for training and FAQ development.')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        ContentSchema::descriptionField(
                            name: 'question',
                            label: 'Question',
                            rows: 4,
                            required: true,
                            placeholder: 'e.g., What are the requirements for joining the fellowship? How often do you meet?',
                            helperText: 'Enter the question exactly as it was asked by the participant. Include any relevant context that might help with follow-up.',
                        ),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mission.school.name')
                    ->label('School/Mission')
                    ->description(fn ($record) => $record->mission?->school?->address)
                    ->icon('heroicon-o-academic-cap')
                    ->searchable(['name'])
                    ->sortable(),

                TextColumn::make('question')
                    ->label('Question')
                    ->limit(100)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($record) => $record->question),

                TextColumn::make('created_at')
                    ->label('Asked On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip(fn ($record) => 'Asked: '.$record->created_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                SelectFilter::make('mission_id')
                    ->label('Mission/School')
                    ->relationship('mission.school', 'name')
                    ->searchable()
                    ->placeholder('All Missions'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view mission question')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit mission question')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission question')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission question')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete mission question')),
                ])->visible(fn () => userCan('delete mission question')),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Export Questions')
                    ->icon('heroicon-m-inbox-arrow-down')
                    ->exporter(MissionQuestionExporter::class)
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
            'index' => ListMissionQuestions::route('/'),
            'create' => CreateMissionQuestion::route('/create'),
            'view' => ViewMissionQuestion::route('/{record}'),
            'edit' => EditMissionQuestion::route('/{record}/edit'),
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
        return userCan('viewAny mission question');
    }
}
