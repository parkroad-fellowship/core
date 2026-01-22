<?php

namespace App\Filament\Resources\PrayerRequests;

use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\PrayerRequests\Pages\CreatePrayerRequest;
use App\Filament\Resources\PrayerRequests\Pages\EditPrayerRequest;
use App\Filament\Resources\PrayerRequests\Pages\ListPrayerRequests;
use App\Filament\Resources\PrayerRequests\Pages\ViewPrayerRequest;
use App\Models\PrayerRequest;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class PrayerRequestResource extends Resource
{
    protected static ?string $model = PrayerRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-heart';

    protected static string|\UnitEnum|null $navigationGroup = 'Prayer Secretary';

    protected static ?string $modelLabel = 'Prayer Request';

    protected static ?string $pluralModelLabel = 'Prayer Requests';

    protected static ?string $navigationTooltip = 'Manage member prayer requests and intercession';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Requester Information')
                    ->description('Select the member who is submitting this prayer request')
                    ->icon('heroicon-o-user')
                    ->schema([
                        StatusSchema::relationshipSelect(
                            name: 'member_id',
                            label: 'Member Name',
                            relationship: 'member',
                            titleAttribute: 'full_name',
                            required: true,
                            helperText: 'Choose the member who is making this prayer request',
                        ),
                    ])
                    ->collapsible(),

                Section::make('Prayer Request Details')
                    ->description('Provide information about what you would like prayer for')
                    ->icon('heroicon-o-heart')
                    ->schema([
                        ContentSchema::titleField(
                            name: 'title',
                            label: 'Prayer Subject',
                            placeholder: 'e.g., Healing for a family member, Guidance for job search',
                            required: false,
                            helperText: 'Give a brief subject for this prayer request (optional but helpful)',
                        ),

                        ContentSchema::descriptionField(
                            name: 'description',
                            label: 'Prayer Request Details',
                            rows: 5,
                            required: true,
                            placeholder: 'Please describe what you would like prayer for. You can include as much detail as you feel comfortable sharing...',
                            helperText: 'Share the details of your prayer request. This information will be kept confidential.',
                        ),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.full_name')
                    ->label('Member')
                    ->icon('heroicon-o-user')
                    ->sortable()
                    ->searchable(['full_name']),

                TextColumn::make('title')
                    ->label('Prayer Subject')
                    ->icon('heroicon-o-heart')
                    ->wrap()
                    ->searchable()
                    ->placeholder('No subject provided')
                    ->description(fn ($record) => $record->description ? Str::limit($record->description, 60) : null),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(100)
                    ->wrap()
                    ->tooltip(fn ($record) => $record->description)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Requested On')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->tooltip(fn ($record) => 'Requested: '.$record->created_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                SelectFilter::make('member_id')
                    ->label('Member')
                    ->relationship('member', 'full_name')
                    ->searchable()
                    ->placeholder('All Members'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view prayer request')),
                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit prayer request')),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete prayer request')),
                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete prayer request')),
                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete prayer request')),
                ])->visible(fn () => userCan('delete prayer request')),
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
            'index' => ListPrayerRequests::route('/'),
            'create' => CreatePrayerRequest::route('/create'),
            'view' => ViewPrayerRequest::route('/{record}'),
            'edit' => EditPrayerRequest::route('/{record}/edit'),
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
        return userCan('viewAny prayer request');
    }
}
