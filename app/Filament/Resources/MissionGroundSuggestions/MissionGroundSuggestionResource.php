<?php

namespace App\Filament\Resources\MissionGroundSuggestions;

use App\Enums\PRFMissionGroundSuggestionStatus;
use App\Filament\Resources\MissionGroundSuggestions\Pages\CreateMissionGroundSuggestion;
use App\Filament\Resources\MissionGroundSuggestions\Pages\EditMissionGroundSuggestion;
use App\Filament\Resources\MissionGroundSuggestions\Pages\ListMissionGroundSuggestions;
use App\Filament\Resources\MissionGroundSuggestions\Pages\ViewMissionGroundSuggestion;
use App\Models\MissionGroundSuggestion;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

class MissionGroundSuggestionResource extends Resource
{
    protected static ?string $model = MissionGroundSuggestion::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static string|\UnitEnum|null $navigationGroup = 'Missions Secretary';

    protected static ?string $modelLabel = 'Mission Ground Suggestion';

    protected static ?string $pluralModelLabel = 'Mission Ground Suggestions';

    protected static ?string $navigationTooltip = 'Manage suggested mission grounds and locations';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Suggestor Information')
                    ->description('Select the person suggesting this mission ground')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Select::make('suggestor_id')
                            ->label('Suggestor')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship('suggestor', 'full_name')
                            ->helperText('Select the member who is suggesting this mission ground'),
                    ]),

                Section::make('Location Details')
                    ->description('Information about the suggested mission ground')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextInput::make('name')
                            ->label('Location Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Enter the name of the suggested mission ground')
                            ->placeholder('e.g., Agege Community Center'),

                        TextInput::make('contact_person')
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

                Section::make('Status & Notes')
                    ->description('Current status and additional notes')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->options(PRFMissionGroundSuggestionStatus::getOptions())
                            ->default(PRFMissionGroundSuggestionStatus::PENDING->value)
                            ->helperText('Current status of this suggestion')
                            ->hiddenOn('create'),

                        Textarea::make('notes')
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
                TextColumn::make('suggestor.full_name')
                    ->label('Suggested By')
                    ->description(fn ($record) => $record->suggestor?->email)
                    ->searchable(['full_name'])
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Location Name')
                    ->description(fn ($record) => $record->contact_person)
                    ->icon('heroicon-o-map-pin')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('contact_person')
                    ->label('Contact Person')
                    ->icon('heroicon-o-user')
                    ->searchable()
                    ->sortable(),

                PhoneColumn::make('contact_number')
                    ->label('Contact Number')
                    ->displayFormat(PhoneInputNumberType::INTERNATIONAL)
                    ->icon('heroicon-o-phone'),

                TextColumn::make('status')
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

                TextColumn::make('created_at')
                    ->label('Suggested On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->tooltip(fn ($record) => 'Suggested: '.$record->created_at->format('F j, Y \a\t g:i A')),

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

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PRFMissionGroundSuggestionStatus::getOptions())
                    ->placeholder('All Statuses'),

                SelectFilter::make('suggestor_id')
                    ->label('Suggested By')
                    ->relationship('suggestor', 'full_name')
                    ->searchable()
                    ->placeholder('All Suggestors'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info'),
                    EditAction::make()
                        ->color('warning'),
                    Action::make('initiate_contact')
                        ->label('Initiate Contact')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('info')
                        ->action(function ($record) {
                            $record->update(['status' => PRFMissionGroundSuggestionStatus::INITIATED_CONTACT->value]);
                        })
                        ->visible(fn ($record) => $record->status === PRFMissionGroundSuggestionStatus::PENDING->value)
                        ->requiresConfirmation(),
                    Action::make('schedule_visit')
                        ->label('Schedule Visit')
                        ->icon('heroicon-o-calendar')
                        ->color('info')
                        ->action(function ($record) {
                            $record->update(['status' => PRFMissionGroundSuggestionStatus::VISIT_SCHEDULED->value]);
                        })
                        ->visible(fn ($record) => $record->status === PRFMissionGroundSuggestionStatus::INITIATED_CONTACT->value)
                        ->requiresConfirmation(),
                    Action::make('secure_mission')
                        ->label('Secure Mission')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->update(['status' => PRFMissionGroundSuggestionStatus::MISSION_SECURED->value]);
                        })
                        ->visible(fn ($record) => $record->status === PRFMissionGroundSuggestionStatus::VISIT_SCHEDULED->value)
                        ->requiresConfirmation(),
                    Action::make('ignore')
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    BulkAction::make('initiate_contact')
                        ->label('Initiate Contact')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('info')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['status' => PRFMissionGroundSuggestionStatus::INITIATED_CONTACT->value]));
                        })
                        ->requiresConfirmation(),
                    BulkAction::make('ignore')
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
            'index' => ListMissionGroundSuggestions::route('/'),
            'create' => CreateMissionGroundSuggestion::route('/create'),
            'view' => ViewMissionGroundSuggestion::route('/{record}'),
            'edit' => EditMissionGroundSuggestion::route('/{record}/edit'),
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
