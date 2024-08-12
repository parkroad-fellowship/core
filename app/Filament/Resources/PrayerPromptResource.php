<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFPromptFrequency;
use App\Enums\PRFPromptTime;
use App\Filament\Resources\PrayerPromptResource\Pages;
use App\Filament\Resources\PrayerPromptResource\RelationManagers;
use App\Models\PrayerPrompt;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class PrayerPromptResource extends Resource
{
    protected static ?string $model = PrayerPrompt::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Prayer Secretary';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Select::make('frequency')
                    ->required()
                    ->options(PRFPromptFrequency::getOptions())
                    ->default(PRFPromptFrequency::DAILY->value),
                Forms\Components\Select::make('day_of_week')
                    ->options([
                        Carbon::SUNDAY => 'Sunday',
                        Carbon::MONDAY => 'Monday',
                        Carbon::TUESDAY => 'Tuesday',
                        Carbon::WEDNESDAY => 'Wednesday',
                        Carbon::THURSDAY => 'Thursday',
                        Carbon::FRIDAY => 'Friday',
                        Carbon::SATURDAY => 'Saturday',
                    ])
                    ->required(),
                Forms\Components\Select::make('time_of_day')
                    ->required()
                    ->options(PRFPromptTime::getOptions())
                    ->default(PRFPromptTime::MORNING->value),
                Forms\Components\Select::make('is_active')
                    ->required()
                    ->hiddenOn(['create'])
                    ->options(PRFActiveStatus::getOptions())
                    ->default(PRFActiveStatus::ACTIVE->value),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('frequency')
                    ->label('Frequency')
                    ->formatStateUsing(fn ($record) => PRFPromptFrequency::fromValue($record->frequency)->name)
                    ->sortable(),
                Tables\Columns\TextColumn::make('day_of_week')
                    ->formatStateUsing(fn ($record) => Carbon::create()->dayOfWeek($record->day_of_week)->dayName)
                    ->sortable(),
                Tables\Columns\TextColumn::make('time_of_day')
                    ->label('Time of Day')
                    ->formatStateUsing(fn ($record) => PRFPromptTime::fromValue($record->time_of_day)->name)
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
                Tables\Actions\EditAction::make(),
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
            RelationManagers\PrayerResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPrayerPrompts::route('/'),
            'create' => Pages\CreatePrayerPrompt::route('/create'),
            'view' => Pages\ViewPrayerPrompt::route('/{record}'),
            'edit' => Pages\EditPrayerPrompt::route('/{record}/edit'),
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
