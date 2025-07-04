<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnnouncementResource\Pages;
use App\Filament\Resources\AnnouncementResource\RelationManagers;
use App\Models\Announcement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Organising Secretary';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Announcement';

    protected static ?string $pluralModelLabel = 'Announcements';

    protected static ?string $navigationTooltip = 'Manage church announcements and communications';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Announcement Details')
                    ->description('Provide the main information for this announcement')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Announcement Title')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A clear and descriptive title for the announcement')
                            ->placeholder('Enter announcement title'),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Publish Date & Time')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->timezone(Auth::user()->timezone ?? 'UTC')
                            ->helperText('When this announcement should be published')
                            ->displayFormat('M j, Y g:i A')
                            ->default(now()),
                    ]),

                Forms\Components\Section::make('Content')
                    ->description('Write the announcement content')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('Announcement Content')
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Provide the full content of the announcement')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'bulletList',
                                'orderedList',
                                'link',
                                'h2',
                                'h3',
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Announcement $record): string => str($record->content)->stripTags()->limit(100)->toString()
                    )
                    ->wrap(),

                Tables\Columns\TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->badge()
                    ->color(fn (Announcement $record): string => $record->published_at?->isFuture() ? 'warning' : 'success'
                    )
                    ->icon(fn (Announcement $record): string => $record->published_at?->isFuture() ? 'heroicon-o-clock' : 'heroicon-o-check-circle'
                    )
                    ->tooltip(fn (Announcement $record): string => $record->published_at?->isFuture()
                            ? 'Scheduled for future publication'
                            : 'Already published'
                    ),

                Tables\Columns\TextColumn::make('announcement_groups_count')
                    ->label('Target Groups')
                    ->counts('announcementGroups')
                    ->badge()

                    ->color('info')
                    ->icon('heroicon-o-user-group')
                    ->tooltip('Number of groups this announcement targets'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->native(false),

                Tables\Filters\Filter::make('published')
                    ->label('Published Announcements')
                    ->query(fn (Builder $query): Builder => $query->where('published_at', '<=', now())
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('scheduled')
                    ->label('Scheduled Announcements')
                    ->query(fn (Builder $query): Builder => $query->where('published_at', '>', now())
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('recent')
                    ->label('Recent (Last 30 days)')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))
                    )
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->visible(fn () => userCan('view announcement'))
                    ->tooltip('View announcement details'),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => userCan('edit announcement'))
                    ->tooltip('Edit this announcement'),

                Tables\Actions\Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->tooltip('Duplicate this announcement')
                    ->action(function (Announcement $record) {
                        $newRecord = $record->replicate([
                            'announcement_groups_count',
                        ]);
                        $newRecord->title = $record->title.' (Copy)';
                        $newRecord->published_at = now()->addHour();
                        $newRecord->save();
                    })
                    ->visible(fn () => userCan('create announcement')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete announcement')),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete announcement')),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete announcement')),

                    Tables\Actions\BulkAction::make('bulk_publish')
                        ->label('Publish Selected')
                        ->icon('heroicon-o-megaphone')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                $record->update(['published_at' => now()]);
                            });
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit announcement')),
                ]),
            ])
            ->defaultSort('published_at', 'desc')
            ->striped()
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AnnouncementGroupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'view' => Pages\ViewAnnouncement::route('/{record}'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
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
        return userCan('viewAny announcement');
    }
}
