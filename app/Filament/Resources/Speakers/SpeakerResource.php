<?php

namespace App\Filament\Resources\Speakers;

use App\Filament\Forms\Schemas\ContactSchema;
use App\Filament\Forms\Schemas\PersonalInfoSchema;
use App\Filament\Resources\Speakers\Pages\CreateSpeaker;
use App\Filament\Resources\Speakers\Pages\EditSpeaker;
use App\Filament\Resources\Speakers\Pages\ListSpeakers;
use App\Filament\Resources\Speakers\Pages\ViewSpeaker;
use App\Filament\Resources\Speakers\RelationManagers\EventSpeakersRelationManager;
use App\Models\Speaker;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SpeakerResource extends Resource
{
    protected static ?string $model = Speaker::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-microphone';

    protected static ?string $navigationLabel = 'Speakers';

    protected static ?string $modelLabel = 'Speaker';

    protected static ?string $pluralModelLabel = 'Speakers';

    protected static string|\UnitEnum|null $navigationGroup = 'Prayer Secretary';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationTooltip = 'Manage speakers and their speaking engagements';

    protected static int $globalSearchResultsLimit = 20;

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Title' => $record->title ?? 'No title',
            'Phone' => $record->phone_number,
            'Events' => $record->eventSpeakers_count ?? $record->eventSpeakers()->count().' speaking engagements',
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'title', 'phone_number', 'bio'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();

        return $count > 10 ? 'success' : ($count > 5 ? 'warning' : 'gray');
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = static::getNavigationBadge();

        return $count.' speaker'.($count !== 1 ? 's' : '').' registered';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Speaker Information')
                    ->description('Enter the speaker\'s name and professional title')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                PersonalInfoSchema::fullNameField(
                                    name: 'name',
                                    label: 'Full Name',
                                    required: true,
                                )
                                    ->placeholder('e.g., Rev. John Mwangi')
                                    ->helperText('Full name as it should appear in event programs'),

                                PersonalInfoSchema::titleField(
                                    name: 'title',
                                    label: 'Title or Position',
                                    required: false,
                                )
                                    ->placeholder('e.g., Senior Pastor, Minister, Evangelist')
                                    ->helperText('Professional or ministerial title'),
                            ]),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Contact Details')
                    ->description('How to reach the speaker for event coordination')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                ContactSchema::phoneField(
                                    name: 'phone_number',
                                    label: 'Phone Number',
                                    defaultCountry: 'KE',
                                    required: true,
                                    helperText: 'Primary contact number for coordination',
                                ),

                                ContactSchema::emailField(
                                    name: 'email',
                                    label: 'Email Address',
                                    required: false,
                                    helperText: 'Optional email for formal communication',
                                )
                                    ->placeholder('e.g., speaker@example.com'),
                            ]),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Biography')
                    ->description('Background information about the speaker')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        PersonalInfoSchema::bioField(
                            name: 'bio',
                            label: 'Speaker Biography',
                            rows: 5,
                            maxLength: 2000,
                            required: false,
                        )
                            ->placeholder('Write a brief biography about the speaker. Include their background, ministry experience, education, and areas of expertise. This information may be used in event programs and promotional materials.')
                            ->helperText('This biography will appear in event programs and promotional materials'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Speaker Name')
                    ->description(fn (Speaker $record): ?string => $record->title)
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->tooltip('Full name of the speaker'),
                TextColumn::make('phone_number')
                    ->label('Phone')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->copyMessageDuration(1500)
                    ->tooltip('Click to copy phone number'),
                TextColumn::make('title')
                    ->label('Title/Position')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('No title')
                    ->tooltip('Professional or ministerial title'),
                TextColumn::make('eventSpeakers_count')
                    ->label('Speaking Events')
                    ->counts('eventSpeakers')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state < 5 => 'warning',
                        default => 'success',
                    })
                    ->icon('heroicon-o-microphone')
                    ->tooltip('Total number of speaking engagements')
                    ->sortable(),
                TextColumn::make('bio')
                    ->label('Biography')
                    ->limit(60)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 60) {
                            return null;
                        }

                        return $state;
                    })
                    ->wrap()
                    ->placeholder('No biography provided')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn (Speaker $record): string => 'Added: '.$record->created_at->format('F j, Y \a\t g:i A')),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn (Speaker $record): string => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),
                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Not deleted'),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),
                Filter::make('has_title')
                    ->label('Has Title')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('title'))
                    ->toggle(),
                Filter::make('has_bio')
                    ->label('Has Biography')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('bio'))
                    ->toggle(),
                Filter::make('active_speakers')
                    ->label('Active Speakers')
                    ->query(fn (Builder $query): Builder => $query->whereHas('eventSpeakers'))
                    ->default()
                    ->toggle(),
                Filter::make('recent_speakers')
                    ->label('Added Recently')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30)))
                    ->toggle(),
                SelectFilter::make('event_count')
                    ->label('Speaking Engagements')
                    ->placeholder('All Speakers')
                    ->options([
                        'none' => 'No Events (0)',
                        'few' => 'Few Events (1-4)',
                        'many' => 'Many Events (5+)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'none' => $query->doesntHave('eventSpeakers'),
                            'few' => $query->has('eventSpeakers', '>=', 1)->has('eventSpeakers', '<=', 4),
                            'many' => $query->has('eventSpeakers', '>=', 5),
                            default => $query,
                        };
                    }),
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('name')
                            ->label('Speaker Name'),
                        TextConstraint::make('title')
                            ->label('Title/Position'),
                        TextConstraint::make('phone_number')
                            ->label('Phone Number'),
                        TextConstraint::make('bio')
                            ->label('Biography'),
                        DateConstraint::make('created_at')
                            ->label('Date Added'),
                        RelationshipConstraint::make('eventSpeakers')
                            ->label('Speaking Events')
                            ->multiple(),
                    ]),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->modalHeading(fn (Speaker $record) => "Speaker Profile: {$record->name}"),
                    EditAction::make()
                        ->color('warning')
                        ->successNotificationTitle('Speaker updated successfully'),
                    DeleteAction::make()
                        ->successNotificationTitle('Speaker deleted successfully'),
                    ForceDeleteAction::make(),
                    RestoreAction::make()
                        ->successNotificationTitle('Speaker restored successfully'),
                ])->label('Actions')
                    ->color('primary')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button()
                    ->tooltip('Speaker Actions'),
                Action::make('contact')
                    ->label('Contact')
                    ->icon('heroicon-m-phone')
                    ->color('success')
                    ->url(fn (Speaker $record): string => "tel:{$record->phone_number}")
                    ->openUrlInNewTab(false)
                    ->tooltip('Call speaker directly')
                    ->visible(fn (Speaker $record): bool => ! empty($record->phone_number)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete speaker')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete speaker')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete speaker')),
                    BulkAction::make('updateTitle')
                        ->label('Update Title')
                        ->icon('heroicon-m-briefcase')
                        ->color('warning')
                        ->form([
                            TextInput::make('title')
                                ->label('New Title')
                                ->placeholder('e.g., Senior Pastor, Minister')
                                ->helperText('This title will be applied to all selected speakers'),
                            Checkbox::make('overwrite_existing')
                                ->label('Overwrite existing titles')
                                ->helperText('Check to replace existing titles, uncheck to only set for speakers without titles'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if (empty($record->title) || $data['overwrite_existing']) {
                                    $record->update(['title' => $data['title']]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Titles updated successfully')
                                ->body("Updated titles for {$count} speakers")
                                ->send();
                        })
                        ->visible(fn () => userCan('edit speaker')),
                    BulkAction::make('massContact')
                        ->label('Export Contact Info')
                        ->icon('heroicon-m-phone')
                        ->color('info')
                        ->action(function (Collection $records): void {
                            $contacts = $records->map(function (Speaker $speaker) {
                                return [
                                    'name' => $speaker->name,
                                    'phone' => $speaker->phone_number,
                                    'title' => $speaker->title ?? 'No title',
                                ];
                            })->toArray();

                            // This would typically generate a file download
                            // For now, we'll just show a notification
                            Notification::make()
                                ->success()
                                ->title('Contact information prepared')
                                ->body('Contact info for '.count($contacts).' speakers is ready for export')
                                ->send();
                        })
                        ->successNotificationTitle('Contact information prepared')
                        ->visible(fn () => userCan('view speaker')),
                ])->visible(fn () => userCan('delete speaker') || userCan('edit speaker')),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            EventSpeakersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpeakers::route('/'),
            'create' => CreateSpeaker::route('/create'),
            'view' => ViewSpeaker::route('/{record}'),
            'edit' => EditSpeaker::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('eventSpeakers')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getDefaultEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['eventSpeakers'])
            ->withCount('eventSpeakers');
    }

    public static function canAccess(): bool
    {
        return userCan('viewAny speaker');
    }
}
