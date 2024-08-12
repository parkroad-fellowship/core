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

class MissionQuestionResource extends Resource
{
    protected static ?string $model = MissionQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('mission_id')
                    ->required()
                    ->relationship(
                        name: 'mission.school',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                    )
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('question')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mission.school.name'),
                Tables\Columns\TextColumn::make('question')
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Asked On')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
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
}
