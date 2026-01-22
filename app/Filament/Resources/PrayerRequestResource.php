<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Str;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\PrayerRequestResource\Pages\ListPrayerRequests;
use App\Filament\Resources\PrayerRequestResource\Pages\CreatePrayerRequest;
use App\Filament\Resources\PrayerRequestResource\Pages\ViewPrayerRequest;
use App\Filament\Resources\PrayerRequestResource\Pages\EditPrayerRequest;
use App\Filament\Resources\PrayerRequestResource\Pages;
use App\Models\PrayerRequest;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PrayerRequestResource extends Resource
{
    protected static ?string $model = PrayerRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-heart';

    protected static string | \UnitEnum | null $navigationGroup = 'Prayer Secretary';

    protected static ?string $modelLabel = 'Prayer Request';

    protected static ?string $pluralModelLabel = 'Prayer Requests';

    protected static ?string $navigationTooltip = 'Manage member prayer requests and intercession';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Request Information')
                    ->description('Who is making this prayer request')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Select::make('member_id')
                            ->label('Member')
                            ->relationship('member', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the member making this prayer request'),
                    ]),

                Section::make('Prayer Details')
                    ->description('Details of the prayer request')
                    ->icon('heroicon-o-heart')
                    ->schema([
                        TextInput::make('title')
                            ->label('Prayer Title')
                            ->maxLength(255)
                            ->helperText('Brief title or subject of the prayer request')
                            ->placeholder('e.g., Healing, Job Search, Family Issue'),

                        Textarea::make('description')
                            ->label('Prayer Description')
                            ->required()
                            ->rows(5)
                            ->helperText('Detailed description of the prayer request')
                            ->placeholder('Please provide details about what you would like prayer for...')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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
                    ->label('Prayer Title')
                    ->icon('heroicon-o-heart')
                    ->wrap()
                    ->searchable()
                    ->placeholder('No title provided')
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
