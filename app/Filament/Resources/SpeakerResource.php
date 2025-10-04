<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SpeakerResource\Pages;
use App\Filament\Resources\SpeakerResource\RelationManagers;
use App\Models\Speaker;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class SpeakerResource extends Resource
{
    protected static ?string $model = Speaker::class;

    protected static ?string $navigationIcon = 'heroicon-o-microphone';

    protected static ?string $navigationLabel = 'Speakers';

    protected static ?string $modelLabel = 'Speaker';

    protected static ?string $pluralModelLabel = 'Speakers';

    protected static ?string $navigationGroup = 'Prayer Secretary';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationTooltip = 'Manage speakers and their speaking engagements';

    protected static int $globalSearchResultsLimit = 20;

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Speaker Information')
                    ->description('Basic information about the speaker')
                    ->icon('heroicon-o-user-circle')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter speaker\'s full name')
                                    ->autocapitalize()
                                    ->helperText('Full name as it should appear in programs')
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('title')
                                    ->label('Title/Position')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Senior Pastor, Minister, Evangelist')
                                    ->helperText('Professional or ministerial title')
                                    ->columnSpan(1),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                PhoneInput::make('phone_number')
                                    ->label('📱 Phone Number')
                                    ->helperText('Primary contact phone number for coordination')
                                    ->required()
                                    ->defaultCountry('KE'),
                                Forms\Components\TextInput::make('email')
                                    ->label('📧 Email Address')
                                    ->email()
                                    ->helperText('Optional email address for contacting the speaker'),
                            ])->columns(2),
                    ])
                    ->columns(1),
                Forms\Components\Section::make('About the Speaker')
                    ->description('Detailed information about the speaker')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->label('Biography')
                            ->placeholder('Write a brief biography about the speaker... Include their background, ministry experience, and areas of expertise.')
                            ->rows(5)
                            ->maxLength(2000)
                            ->hint('Maximum 2000 characters')
                            ->hintColor('gray')
                            ->helperText('This biography may be used in event programs and promotional materials'),
                    ])
                    ->columns(1),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Speaker Name')
                    ->description(fn (Speaker $record): ?string => $record->title)
                    ->icon('heroicon-m-user')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->copyMessageDuration(1500)
                    ->tooltip('Click to copy phone number'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title/Position')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->tooltip('Professional or ministerial title'),
                Tables\Columns\TextColumn::make('eventSpeakers_count')
                    ->label('Speaking Events')
                    ->counts('eventSpeakers')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state < 5 => 'warning',
                        default => 'success',
                    })
                    ->icon('heroicon-o-microphone')
                    ->tooltip('Number of speaking engagements')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bio')
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn (Speaker $record): string => 'Added: '.$record->created_at->format('F j, Y \a\t g:i A')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn (Speaker $record): string => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),
                Tables\Filters\Filter::make('has_title')
                    ->label('Has Title')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('title'))
                    ->toggle(),
                Tables\Filters\Filter::make('has_bio')
                    ->label('Has Biography')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('bio'))
                    ->toggle(),
                Tables\Filters\Filter::make('active_speakers')
                    ->label('Active Speakers')
                    ->query(fn (Builder $query): Builder => $query->whereHas('eventSpeakers'))
                    ->default()
                    ->toggle(),
                Tables\Filters\Filter::make('recent_speakers')
                    ->label('Added Recently')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30)))
                    ->toggle(),
                Tables\Filters\SelectFilter::make('event_count')
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
                Tables\Filters\QueryBuilder::make()
                    ->constraints([
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('name')
                            ->label('Speaker Name'),
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('title')
                            ->label('Title/Position'),
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('phone_number')
                            ->label('Phone Number'),
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('bio')
                            ->label('Biography'),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('created_at')
                            ->label('Date Added'),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('eventSpeakers')
                            ->label('Speaking Events')
                            ->multiple(),
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->modalHeading(fn (Speaker $record) => "Speaker Profile: {$record->name}"),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->successNotificationTitle('Speaker updated successfully'),
                    Tables\Actions\DeleteAction::make()
                        ->successNotificationTitle('Speaker deleted successfully'),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make()
                        ->successNotificationTitle('Speaker restored successfully'),
                ])->label('Actions')
                    ->color('primary')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button()
                    ->tooltip('Speaker Actions'),
                Tables\Actions\Action::make('contact')
                    ->label('Contact')
                    ->icon('heroicon-m-phone')
                    ->color('success')
                    ->url(fn (Speaker $record): string => "tel:{$record->phone_number}")
                    ->openUrlInNewTab(false)
                    ->tooltip('Call speaker directly')
                    ->visible(fn (Speaker $record): bool => ! empty($record->phone_number)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete speaker')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete speaker')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete speaker')),
                    Tables\Actions\BulkAction::make('updateTitle')
                        ->label('Update Title')
                        ->icon('heroicon-m-briefcase')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('title')
                                ->label('New Title')
                                ->placeholder('Enter title for selected speakers')
                                ->helperText('This will be applied to all selected speakers'),
                            Forms\Components\Checkbox::make('overwrite_existing')
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
                    Tables\Actions\BulkAction::make('massContact')
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
            RelationManagers\EventSpeakersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpeakers::route('/'),
            'create' => Pages\CreateSpeaker::route('/create'),
            'view' => Pages\ViewSpeaker::route('/{record}'),
            'edit' => Pages\EditSpeaker::route('/{record}/edit'),
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
