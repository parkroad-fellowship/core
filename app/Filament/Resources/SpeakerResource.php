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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getModel()::count();

        return $count > 10 ? 'success' : ($count > 5 ? 'warning' : 'gray');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Speaker Information')
                    ->description('Basic information about the speaker')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter speaker\'s full name')
                                    ->autocapitalize()
                                    ->columnSpan(1),
                                Forms\Components\TextInput::make('title')
                                    ->label('Job Title')
                                    ->maxLength(255)
                                    ->placeholder('e.g., Senior Software Engineer')
                                    ->columnSpan(1),
                            ]),
                        PhoneInput::make('phone_number')
                            ->label('📱 Phone Number')
                            ->helperText('Primary contact phone number')
                            ->required()
                            ->defaultCountry('KE'),
                    ]),
                Forms\Components\Section::make('About the Speaker')
                    ->description('Detailed information about the speaker')
                    ->schema([
                        Forms\Components\Textarea::make('bio')
                            ->label('Biography')
                            ->placeholder('Write a brief biography about the speaker...')
                            ->rows(4)
                            ->maxLength(1000)
                            ->hint('Maximum 1000 characters')
                            ->hintColor('gray'),
                    ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Speaker Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-m-user')
                    ->description(fn (Speaker $record): ?string => $record->title),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone')
                    ->icon('heroicon-m-phone')
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->copyMessageDuration(1500),
                Tables\Columns\TextColumn::make('title')
                    ->label('Job Title')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('No title set'),
                Tables\Columns\TextColumn::make('eventSpeakers_count')
                    ->label('Events')
                    ->counts('eventSpeakers')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state < 5 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('bio')
                    ->label('Bio')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn (Speaker $record): string => $record->created_at->format('F j, Y g:i A')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('has_title')
                    ->label('Has Job Title')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('title')),
                Tables\Filters\Filter::make('has_bio')
                    ->label('Has Biography')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('bio')),
                Tables\Filters\Filter::make('active_speakers')
                    ->label('Active Speakers')
                    ->query(fn (Builder $query): Builder => $query->whereHas('eventSpeakers')),
                Tables\Filters\Filter::make('recent_speakers')
                    ->label('Added Recently')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))),
                Tables\Filters\SelectFilter::make('event_count')
                    ->label('Speaking Engagements')
                    ->options([
                        'none' => 'No Events',
                        'few' => '1-4 Events',
                        'many' => '5+ Events',
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
                            ->label('Job Title'),
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
                        ->color('info'),
                    Tables\Actions\EditAction::make()
                        ->color('warning'),
                    Tables\Actions\Action::make('clone')
                        ->label('Clone Speaker')
                        ->icon('heroicon-m-square-2-stack')
                        ->color('gray')
                        ->form([
                            Forms\Components\TextInput::make('name')
                                ->label('New Speaker Name')
                                ->required()
                                ->default(fn (Speaker $record) => "Copy of {$record->name}"),
                            Forms\Components\Toggle::make('copy_bio')
                                ->label('Copy biography')
                                ->default(true),
                        ])
                        ->action(function (array $data, Speaker $record): void {
                            $newSpeaker = $record->replicate();
                            $newSpeaker->name = $data['name'];
                            if (! $data['copy_bio']) {
                                $newSpeaker->bio = null;
                            }
                            $newSpeaker->save();
                        })
                        ->successNotificationTitle('Speaker cloned successfully'),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ])->label('Actions')
                    ->color('primary')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button(),
                Tables\Actions\Action::make('contact')
                    ->label('Contact')
                    ->icon('heroicon-m-phone')
                    ->color('success')
                    ->url(fn (Speaker $record): string => "tel:{$record->phone_number}")
                    ->openUrlInNewTab(false)
                    ->visible(fn (Speaker $record): bool => ! empty($record->phone_number)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateTitle')
                        ->label('Update Job Title')
                        ->icon('heroicon-m-briefcase')
                        ->color('warning')
                        ->form([
                            Forms\Components\TextInput::make('title')
                                ->label('New Job Title')
                                ->placeholder('Enter job title for selected speakers'),
                            Forms\Components\Checkbox::make('overwrite_existing')
                                ->label('Overwrite existing titles')
                                ->helperText('Check to replace existing job titles'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if (empty($record->title) || $data['overwrite_existing']) {
                                    $record->update(['title' => $data['title']]);
                                    $count++;
                                }
                            }
                        })
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Job titles updated')
                                ->body(fn (Collection $records) => "Updated titles for {$records->count()} speakers")
                        ),
                    Tables\Actions\BulkAction::make('massContact')
                        ->label('Export Contact Info')
                        ->icon('heroicon-m-phone')
                        ->color('info')
                        ->action(function (Collection $records): void {
                            $contacts = $records->map(function (Speaker $speaker) {
                                return [
                                    'name' => $speaker->name,
                                    'phone' => $speaker->phone_number,
                                    'title' => $speaker->title,
                                ];
                            })->toArray();

                            // This would typically generate a file download
                            // For now, we'll just show a notification
                        })
                        ->successNotificationTitle('Contact information prepared'),
                ]),
            ]);
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
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return userCan('viewAny speaker');
    }
}
