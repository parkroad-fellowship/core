<?php

namespace App\Filament\Resources\Announcements;

use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Resources\Announcements\Pages\CreateAnnouncement;
use App\Filament\Resources\Announcements\Pages\EditAnnouncement;
use App\Filament\Resources\Announcements\Pages\ListAnnouncements;
use App\Filament\Resources\Announcements\Pages\ViewAnnouncement;
use App\Filament\Resources\Announcements\RelationManagers\AnnouncementGroupsRelationManager;
use App\Models\Announcement;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|\UnitEnum|null $navigationGroup = 'Organising Secretary';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Announcement';

    protected static ?string $pluralModelLabel = 'Announcements';

    protected static ?string $navigationTooltip = 'Manage church announcements and communications';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Announcement Details')
                    ->description('Enter the basic information for this announcement')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        ContentSchema::titleField(
                            name: 'title',
                            label: 'Announcement Title',
                            placeholder: 'e.g., Sunday Service Update, Upcoming Youth Event',
                            helperText: 'Give your announcement a clear, descriptive title that summarizes the main message',
                        ),

                        DateTimePicker::make('published_at')
                            ->label('Publish Date and Time')
                            ->required()
                            ->native(false)
                            ->seconds(false)
                            ->timezone(Auth::user()->timezone ?? 'UTC')
                            ->helperText('Choose when this announcement should be visible to members. Set a future date to schedule it.')
                            ->displayFormat('M j, Y g:i A')
                            ->default(now()),
                    ])
                    ->collapsible(),

                Section::make('Announcement Content')
                    ->description('Write the full message you want to share with members')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        ContentSchema::richEditorField(
                            name: 'content',
                            label: 'Message Content',
                            required: true,
                            helperText: 'Write your announcement message here. Use the formatting tools to add headings, lists, and links.',
                        ),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Announcement $record): string => str($record->content)->stripTags()->limit(100)->toString()
                    )
                    ->wrap(),

                TextColumn::make('published_at')
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

                TextColumn::make('announcement_groups_count')
                    ->label('Target Groups')
                    ->counts('announcementGroups')
                    ->badge()

                    ->color('info')
                    ->icon('heroicon-o-user-group')
                    ->tooltip('Number of groups this announcement targets'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->native(false),

                Filter::make('published')
                    ->label('Published Announcements')
                    ->query(fn (Builder $query): Builder => $query->where('published_at', '<=', now())
                    )
                    ->toggle(),

                Filter::make('scheduled')
                    ->label('Scheduled Announcements')
                    ->query(fn (Builder $query): Builder => $query->where('published_at', '>', now())
                    )
                    ->toggle(),

                Filter::make('recent')
                    ->label('Recent (Last 30 days)')
                    ->query(fn (Builder $query): Builder => $query->where('created_at', '>=', now()->subDays(30))
                    )
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => userCan('view announcement'))
                    ->tooltip('View announcement details'),

                EditAction::make()
                    ->visible(fn () => userCan('edit announcement'))
                    ->tooltip('Edit this announcement'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete announcement')),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete announcement')),

                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete announcement')),

                    BulkAction::make('bulk_publish')
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
            AnnouncementGroupsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'view' => ViewAnnouncement::route('/{record}'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
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
