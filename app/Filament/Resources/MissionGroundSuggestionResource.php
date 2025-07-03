<?php

namespace App\Filament\Resources;

use App\Enums\PRFMissionGroundSuggestionStatus;
use App\Filament\Resources\MissionGroundSuggestionResource\Pages;
use App\Models\MissionGroundSuggestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class MissionGroundSuggestionResource extends Resource
{
    protected static ?string $model = MissionGroundSuggestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Missions Secretary';

    protected static ?string $modelLabel = 'Mission Ground Suggestion';

    protected static ?string $pluralModelLabel = 'Mission Ground Suggestions';

    protected static ?string $navigationTooltip = 'Manage suggested mission grounds and locations';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Suggestor Information')
                    ->description('Select the person suggesting this mission ground')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('suggestor_id')
                            ->label('Suggestor')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship('suggestor', 'full_name')
                            ->helperText('Select the member who is suggesting this mission ground'),
                    ]),

                Forms\Components\Section::make('Location Details')
                    ->description('Information about the suggested mission ground')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Location Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter the name of the suggested mission ground')
                            ->placeholder('e.g., Agege Community Center'),

                        Forms\Components\TextInput::make('contact_person')
                            ->label('Contact Person')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Name of the person to contact at this location')
                            ->placeholder('e.g., Chief John Doe'),

                        PhoneInput::make('contact_number')
                            ->label('Contact Number')
                            ->required()
                            ->helperText('Phone number of the contact person'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Notes')
                    ->description('Current status and additional notes')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options(PRFMissionGroundSuggestionStatus::getOptions())
                            ->default(PRFMissionGroundSuggestionStatus::PENDING->value)
                            ->helperText('Current status of this suggestion')
                            ->hiddenOn('create'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->required()
                            ->rows(4)
                            ->helperText('Additional notes and observations about this location')
                            ->placeholder('Enter any relevant information about the location, accessibility, etc.')
                            ->columnSpanFull()
                            ->hiddenOn('create'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('suggestor.full_name')
                    ->label('Suggested By')
                    ->description(fn ($record) => $record->suggestor?->email)
                    ->searchable(['full_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Location Name')
                    ->description(fn ($record) => $record->contact_person)
                    ->icon('heroicon-o-map-pin')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Contact Person')
                    ->icon('heroicon-o-user')
                    ->searchable()
                    ->sortable(),

                PhoneColumn::make('contact_number')
                    ->label('Contact Number')
                    ->displayFormat(PhoneInputNumberType::INTERNATIONAL)
                    ->icon('heroicon-o-phone'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFMissionGroundSuggestionStatus::getOptions()[$state] ?? 'Unknown')
                    ->color(fn ($state) => match ($state) {
                        PRFMissionGroundSuggestionStatus::PENDING->value => 'warning',
                        PRFMissionGroundSuggestionStatus::INITIATED_CONTACT->value => 'info',
                        PRFMissionGroundSuggestionStatus::VISIT_SCHEDULED->value => 'info',
                        PRFMissionGroundSuggestionStatus::MISSION_SECURED->value => 'success',
                        PRFMissionGroundSuggestionStatus::COMPLETED->value => 'success',
                        PRFMissionGroundSuggestionStatus::IGNORE->value => 'danger',
                        default => 'gray'
                    })
                    ->icon(fn ($state) => match ($state) {
                        PRFMissionGroundSuggestionStatus::PENDING->value => 'heroicon-o-clock',
                        PRFMissionGroundSuggestionStatus::INITIATED_CONTACT->value => 'heroicon-o-chat-bubble-left-right',
                        PRFMissionGroundSuggestionStatus::VISIT_SCHEDULED->value => 'heroicon-o-calendar',
                        PRFMissionGroundSuggestionStatus::MISSION_SECURED->value => 'heroicon-o-check-circle',
                        PRFMissionGroundSuggestionStatus::COMPLETED->value => 'heroicon-o-check-badge',
                        PRFMissionGroundSuggestionStatus::IGNORE->value => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle'
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Suggested On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip(fn ($record) => 'Suggested: '.$record->created_at->format('F j, Y \a\t g:i A')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

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

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(PRFMissionGroundSuggestionStatus::getOptions())
                    ->placeholder('All Statuses'),

                Tables\Filters\SelectFilter::make('suggestor_id')
                    ->label('Suggested By')
                    ->relationship('suggestor', 'full_name')
                    ->searchable()
                    ->placeholder('All Suggestors'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    Tables\Actions\Action::make('initiate_contact')
                        ->label('Initiate Contact')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('info')
                        ->action(function ($record) {
                            $record->update(['status' => PRFMissionGroundSuggestionStatus::INITIATED_CONTACT->value]);
                        })
                        ->visible(fn ($record) => $record->status === PRFMissionGroundSuggestionStatus::PENDING->value)
                        ->requiresConfirmation(),
                    Tables\Actions\Action::make('schedule_visit')
                        ->label('Schedule Visit')
                        ->icon('heroicon-o-calendar')
                        ->color('info')
                        ->action(function ($record) {
                            $record->update(['status' => PRFMissionGroundSuggestionStatus::VISIT_SCHEDULED->value]);
                        })
                        ->visible(fn ($record) => $record->status === PRFMissionGroundSuggestionStatus::INITIATED_CONTACT->value)
                        ->requiresConfirmation(),
                    Tables\Actions\Action::make('secure_mission')
                        ->label('Secure Mission')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->update(['status' => PRFMissionGroundSuggestionStatus::MISSION_SECURED->value]);
                        })
                        ->visible(fn ($record) => $record->status === PRFMissionGroundSuggestionStatus::VISIT_SCHEDULED->value)
                        ->requiresConfirmation(),
                    Tables\Actions\Action::make('ignore')
                        ->label('Ignore')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($record) {
                            $record->update(['status' => PRFMissionGroundSuggestionStatus::IGNORE->value]);
                        })
                        ->visible(fn ($record) => $record->status === PRFMissionGroundSuggestionStatus::PENDING->value)
                        ->requiresConfirmation(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('initiate_contact')
                        ->label('Initiate Contact')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('info')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['status' => PRFMissionGroundSuggestionStatus::INITIATED_CONTACT->value]));
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('ignore')
                        ->label('Ignore Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['status' => PRFMissionGroundSuggestionStatus::IGNORE->value]));
                        })
                        ->requiresConfirmation(),
                ]),
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
            'index' => Pages\ListMissionGroundSuggestions::route('/'),
            'create' => Pages\CreateMissionGroundSuggestion::route('/create'),
            'view' => Pages\ViewMissionGroundSuggestion::route('/{record}'),
            'edit' => Pages\EditMissionGroundSuggestion::route('/{record}/edit'),
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
        return userCan('viewAny mission ground suggestion');
    }
}
