<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\MissionQuestionResource\Pages;
use App\Models\MissionQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MissionQuestionResource extends Resource
{
    protected static ?string $model = MissionQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Mission Question';

    protected static ?string $pluralModelLabel = 'Mission Questions';

    protected static ?string $navigationTooltip = 'Manage questions asked during missions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Mission Information')
                    ->description('Select the mission where this question was asked')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        Forms\Components\Select::make('mission_id')
                            ->label('Mission (School)')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship(
                                name: 'mission.school',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            )
                            ->helperText('Select the mission/school where this question was asked')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Question Details')
                    ->description('Enter the question asked during the mission')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Forms\Components\Textarea::make('question')
                            ->label('Question')
                            ->required()
                            ->rows(4)
                            ->helperText('Enter the question exactly as it was asked')
                            ->placeholder('What question was asked during the mission?')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mission.school.name')
                    ->label('School/Mission')
                    ->description(fn ($record) => $record->mission?->school?->address)
                    ->icon('heroicon-o-academic-cap')
                    ->searchable(['name'])
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('question')
                    ->label('Question')
                    ->limit(100)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($record) => $record->question),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Asked On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip(fn ($record) => 'Asked: ' . $record->created_at->format('F j, Y \a\t g:i A')),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: ' . $record->updated_at->format('F j, Y \a\t g:i A')),
                
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),
                
                Tables\Filters\SelectFilter::make('mission_id')
                    ->label('Mission/School')
                    ->relationship('mission.school', 'name')
                    ->searchable()
                    ->placeholder('All Missions'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view mission question')),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit mission question')),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission question')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete mission question')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete mission question')),
                ])->visible(fn () => userCan('delete mission question')),
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
            'index' => Pages\ListMissionQuestions::route('/'),
            'create' => Pages\CreateMissionQuestion::route('/create'),
            'view' => Pages\ViewMissionQuestion::route('/{record}'),
            'edit' => Pages\EditMissionQuestion::route('/{record}/edit'),
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
