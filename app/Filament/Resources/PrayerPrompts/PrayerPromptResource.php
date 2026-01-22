<?php

namespace App\Filament\Resources\PrayerPrompts;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFPromptFrequency;
use App\Enums\PRFPromptTime;
use App\Filament\Resources\PrayerPrompts\Pages\CreatePrayerPrompt;
use App\Filament\Resources\PrayerPrompts\Pages\EditPrayerPrompt;
use App\Filament\Resources\PrayerPrompts\Pages\ListPrayerPrompts;
use App\Filament\Resources\PrayerPrompts\Pages\ViewPrayerPrompt;
use App\Filament\Resources\PrayerPrompts\RelationManagers\PrayerResponsesRelationManager;
use App\Models\PrayerPrompt;
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
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PrayerPromptResource extends Resource
{
    protected static ?string $model = PrayerPrompt::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-hand-raised';

    protected static string|\UnitEnum|null $navigationGroup = 'Prayer Secretary';

    protected static ?string $modelLabel = 'Prayer Prompt';

    protected static ?string $pluralModelLabel = 'Prayer Prompts';

    protected static ?string $navigationTooltip = 'Manage automated prayer prompts and schedules';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Prayer Prompt Content')
                    ->description('Define the prayer prompt message')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Textarea::make('description')
                            ->label('Prayer Prompt Message')
                            ->required()
                            ->rows(4)
                            ->helperText('Enter the message that will be sent to members as a prayer prompt')
                            ->placeholder('Enter a meaningful prayer prompt message...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Scheduling Settings')
                    ->description('Configure when and how often this prompt is sent')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        Select::make('frequency')
                            ->label('Frequency')
                            ->required()
                            ->options(PRFPromptFrequency::getOptions())
                            ->default(PRFPromptFrequency::WEEKLY->value)
                            ->helperText('How often should this prompt be sent?'),

                        Select::make('day_of_week')
                            ->label('Day of Week')
                            ->options([
                                Carbon::SUNDAY => 'Sunday',
                                Carbon::MONDAY => 'Monday',
                                Carbon::TUESDAY => 'Tuesday',
                                Carbon::WEDNESDAY => 'Wednesday',
                                Carbon::THURSDAY => 'Thursday',
                                Carbon::FRIDAY => 'Friday',
                                Carbon::SATURDAY => 'Saturday',
                            ])
                            ->required()
                            ->helperText('Which day of the week should this be sent?'),

                        Select::make('time_of_day')
                            ->label('Time of Day')
                            ->required()
                            ->options(PRFPromptTime::getOptions())
                            ->default(PRFPromptTime::MORNING->value)
                            ->helperText('What time of day should this be sent?'),
                    ])
                    ->columns(3),

                Section::make('Status Settings')
                    ->description('Control the active status of this prayer prompt')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Select::make('is_active')
                            ->label('Status')
                            ->required()
                            ->hiddenOn(['create'])
                            ->options(PRFActiveStatus::getOptions())
                            ->default(PRFActiveStatus::ACTIVE->value)
                            ->helperText('Set whether this prompt is currently active'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Prayer Message')
                    ->limit(80)
                    ->wrap()
                    ->searchable()
                    ->tooltip(fn ($record) => $record->description),

                TextColumn::make('frequency')
                    ->label('Frequency')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFPromptFrequency::fromValue($state)->getLabel())
                    ->color(fn ($state) => match ($state) {
                        PRFPromptFrequency::DAILY->value => 'info',
                        PRFPromptFrequency::WEEKLY->value => 'warning',
                        PRFPromptFrequency::MONTHLY->value => 'success',
                        PRFPromptFrequency::ONCE->value => 'primary',
                        default => 'gray'
                    })
                    ->icon('heroicon-o-clock')
                    ->sortable(),

                TextColumn::make('day_of_week')
                    ->label('Day')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Carbon::create()->dayOfWeek($state)->dayName)
                    ->color('info')
                    ->icon('heroicon-o-calendar-days')
                    ->sortable(),

                TextColumn::make('time_of_day')
                    ->label('Time')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFPromptTime::fromValue($state)->getLabel())
                    ->color(fn ($state) => match ($state) {
                        PRFPromptTime::MORNING->value => 'warning',
                        PRFPromptTime::AFTERNOON->value => 'info',
                        PRFPromptTime::EVENING->value => 'success',
                        default => 'gray'
                    })
                    ->icon(fn ($state) => match ($state) {
                        PRFPromptTime::MORNING->value => 'heroicon-o-sun',
                        PRFPromptTime::AFTERNOON->value => 'heroicon-o-clock',
                        PRFPromptTime::EVENING->value => 'heroicon-o-moon',
                        default => 'heroicon-o-clock'
                    })
                    ->sortable(),

                TextColumn::make('prayer_responses_count')
                    ->label('Responses')
                    ->counts('prayerResponses')
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->tooltip('Number of prayer responses received'),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFActiveStatus::fromValue($state)->getLabel())
                    ->color(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable(),

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

                SelectFilter::make('frequency')
                    ->label('Frequency')
                    ->options(PRFPromptFrequency::getOptions())
                    ->placeholder('All Frequencies'),

                SelectFilter::make('time_of_day')
                    ->label('Time of Day')
                    ->options(PRFPromptTime::getOptions())
                    ->placeholder('All Times'),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options(PRFActiveStatus::getOptions())
                    ->placeholder('All Statuses'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view prayer prompt')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit prayer prompt')),
                    Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->update([
                                'is_active' => $record->is_active === PRFActiveStatus::ACTIVE->value ? PRFActiveStatus::INACTIVE->value : PRFActiveStatus::ACTIVE->value,
                            ]);
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit prayer prompt')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete prayer prompt')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete prayer prompt')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete prayer prompt')),
                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit prayer prompt')),
                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]));
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('edit prayer prompt')),
                ])->visible(fn () => userCan('delete prayer prompt')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            PrayerResponsesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrayerPrompts::route('/'),
            'create' => CreatePrayerPrompt::route('/create'),
            'view' => ViewPrayerPrompt::route('/{record}'),
            'edit' => EditPrayerPrompt::route('/{record}/edit'),
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
        return userCan('viewAny prayer prompt');
    }
}
