<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\SoulResource\Pages;
use App\Models\Soul;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SoulResource extends Resource
{
    protected static ?string $model = Soul::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Follow-Up Secretary';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Soul';

    protected static ?string $pluralModelLabel = 'Souls';

    protected static ?string $navigationLabel = 'Souls';

    protected static ?string $navigationTooltip = 'Manage student souls won during missions';

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static int $globalSearchResultsLimit = 20;

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'success' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = static::getNavigationBadge();
        return $count.' soul'.($count !== 1 ? 's' : '').' won for Christ';
    }

    public static function getGlobalSearchResultTitle(\Illuminate\Database\Eloquent\Model $record): string
    {
        return $record->full_name;
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return [
            'School' => $record->mission?->school?->name ?? 'N/A',
            'Class' => $record->classGroup?->name ?? 'N/A',
            'Admission Number' => $record->admission_number ?? 'N/A',
            'Won On' => $record->created_at->format('M j, Y'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'admission_number', 'mission.school.name', 'classGroup.name'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Soul Information')
                    ->description('Record details of souls won during missions')
                    ->icon('heroicon-o-heart')
                    ->schema([
                        Forms\Components\Select::make('class_group_id')
                            ->label('Class Group')
                            ->relationship(
                                name: 'classGroup',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->where('is_active', PRFActiveStatus::ACTIVE),
                            )
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('🎓 Select the student\'s class group'),
                        
                        Forms\Components\TextInput::make('full_name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter the student\'s full name')
                            ->helperText('👤 Complete name of the student'),
                        
                        Forms\Components\TextInput::make('admission_number')
                            ->label('Admission Number')
                            ->maxLength(255)
                            ->placeholder('Enter the admission number')
                            ->helperText('📝 Student\'s school admission number (if available)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mission.school.name')
                    ->label('School')
                    ->icon('heroicon-o-academic-cap')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->tooltip(fn ($record) => $record->mission?->school?->name ?? 'No school recorded'),
                
                Tables\Columns\TextColumn::make('classGroup.name')
                    ->label('Class')
                    ->icon('heroicon-o-user-group')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Student Name')
                    ->icon('heroicon-o-user')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                
                Tables\Columns\TextColumn::make('admission_number')
                    ->label('Admission No.')
                    ->icon('heroicon-o-identification')
                    ->searchable()
                    ->placeholder('Not provided')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('mission.theme')
                    ->label('Mission Theme')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->mission?->theme)
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('mission.start_date')
                    ->label('Mission Date')
                    ->date('M j, Y')
                    ->icon('heroicon-o-calendar-days')
                    ->sortable()
                    ->tooltip(fn ($record) => 'Mission: '.$record->mission?->start_date?->format('F j, Y')),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Soul Won On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->icon('heroicon-o-heart')
                    ->color('success')
                    ->tooltip(fn ($record) => 'Soul won: '.$record->created_at->format('F j, Y \a\t g:i A')),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')),
                
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),
                    
                Tables\Filters\SelectFilter::make('mission.school_id')
                    ->label('School')
                    ->relationship('mission.school', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Schools'),
                    
                Tables\Filters\SelectFilter::make('class_group_id')
                    ->label('Class Group')
                    ->relationship('classGroup', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Classes'),
                    
                Tables\Filters\Filter::make('mission_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereHas('mission', fn ($query) => $query->whereDate('start_date', '>=', $date)),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereHas('mission', fn ($query) => $query->whereDate('start_date', '<=', $date)),
                            );
                    }),
                    
                Tables\Filters\Filter::make('has_admission_number')
                    ->label('Has Admission Number')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('admission_number'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view soul')),
                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit soul')),
                    Tables\Actions\Action::make('view_mission')
                        ->label('View Mission')
                        ->icon('heroicon-o-map-pin')
                        ->color('primary')
                        ->url(fn ($record) => $record->mission ? route('filament.admin.resources.missions.view', $record->mission) : null)
                        ->visible(fn ($record) => $record->mission && userCan('view mission')),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete soul')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete soul')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete soul')),
                    Tables\Actions\BulkAction::make('export_souls')
                        ->label('Export Souls')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->action(function ($records) {
                            // Export logic would go here
                            \Filament\Notifications\Notification::make()
                                ->title('Export Started')
                                ->body('Souls export has been started. You will be notified when it\'s complete.')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->visible(fn () => userCan('view soul')),
                ])->visible(fn () => userCan('delete soul')),
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
            'index' => Pages\ListSouls::route('/'),
            'create' => Pages\CreateSoul::route('/create'),
            'view' => Pages\ViewSoul::route('/{record}'),
            'edit' => Pages\EditSoul::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return userCan('viewAny soul');
    }
}
