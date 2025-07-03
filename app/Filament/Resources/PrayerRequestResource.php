<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PrayerRequestResource\Pages;
use App\Models\PrayerRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PrayerRequestResource extends Resource
{
    protected static ?string $model = PrayerRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Prayer Secretary';

    protected static ?string $modelLabel = 'Prayer Request';

    protected static ?string $pluralModelLabel = 'Prayer Requests';

    protected static ?string $navigationTooltip = 'Manage member prayer requests and intercession';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Information')
                    ->description('Who is making this prayer request')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('member_id')
                            ->label('Member')
                            ->relationship('member', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the member making this prayer request'),
                    ]),

                Forms\Components\Section::make('Prayer Details')
                    ->description('Details of the prayer request')
                    ->icon('heroicon-o-heart')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Prayer Title')
                            ->maxLength(255)
                            ->helperText('Brief title or subject of the prayer request')
                            ->placeholder('e.g., Healing, Job Search, Family Issue'),
                        
                        Forms\Components\Textarea::make('description')
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
                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('Member')
                    ->icon('heroicon-o-user')
                    ->sortable()
                    ->searchable(['full_name']),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('Prayer Title')
                    ->icon('heroicon-o-heart')
                    ->wrap()
                    ->searchable()
                    ->placeholder('No title provided')
                    ->description(fn ($record) => $record->description ? \Illuminate\Support\Str::limit($record->description, 60) : null),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(100)
                    ->wrap()
                    ->tooltip(fn ($record) => $record->description)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested On')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->tooltip(fn ($record) => 'Requested: ' . $record->created_at->format('F j, Y \a\t g:i A')),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: ' . $record->updated_at->format('F j, Y \a\t g:i A')),
                
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),
                
                Tables\Filters\SelectFilter::make('member_id')
                    ->label('Member')
                    ->relationship('member', 'full_name')
                    ->searchable()
                    ->placeholder('All Members'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view prayer request')),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit prayer request')),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete prayer request')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete prayer request')),
                    Tables\Actions\RestoreBulkAction::make()
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
            'index' => Pages\ListPrayerRequests::route('/'),
            'create' => Pages\CreatePrayerRequest::route('/create'),
            'view' => Pages\ViewPrayerRequest::route('/{record}'),
            'edit' => Pages\EditPrayerRequest::route('/{record}/edit'),
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
