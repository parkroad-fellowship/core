<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\ChurchResource\Pages;
use App\Models\Church;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Colors\Color;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ChurchResource extends Resource
{
    protected static ?string $model = Church::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = '⚙️ System Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Churches';

    protected static ?string $modelLabel = 'Church';

    protected static ?string $pluralModelLabel = 'Churches';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('⛪ Church Information')
                    ->description('Basic church details and status configuration')
                    ->icon('heroicon-o-building-library')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('🏛️ Church Name')
                                    ->helperText('Enter the official church name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., St. Paul\'s Cathedral')
                                    ->live(onBlur: true)
                                    ->prefixIcon('heroicon-o-building-library'),

                                Forms\Components\Select::make('is_active')
                                    ->label('📊 Status')
                                    ->helperText('Set church availability status')
                                    ->required()
                                    ->options(PRFActiveStatus::getOptions())
                                    ->default(PRFActiveStatus::ACTIVE->value)
                                    ->hiddenOn('create')
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-check-circle'),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('⛪ Church Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-building-library')
                    ->color(Color::Blue)
                    ->tooltip('Church name and location'),

                Tables\Columns\TextColumn::make('members_count')
                    ->label('👥 Members')
                    ->counts('members')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 0 => 'gray',
                        $state <= 10 => 'warning',
                        $state <= 50 => 'info',
                        default => 'success',
                    })
                    ->icon('heroicon-o-users')
                    ->tooltip('Number of registered members'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('📊 Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->size('lg')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->is_active ? 'Church is active' : 'Church is inactive'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Date church was registered'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Last modification date'),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('🗑️ Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Red)
                    ->tooltip('Date church was deleted'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('🗑️ Show Deleted')
                    ->placeholder('Active churches only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('📊 Status Filter')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => '✅ Active Churches',
                        PRFActiveStatus::INACTIVE->value => '❌ Inactive Churches',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->indicator('Status'),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view church')),

                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit church'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Church updated!')
                                ->body('Church information has been updated successfully.')
                        ),

                    Tables\Actions\Action::make('toggle_status')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? Color::Red : Color::Green)
                        ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->action(function ($record) {
                            $record->update(['is_active' => !$record->is_active]);
                            $status = $record->is_active ? 'activated' : 'deactivated';
                            Notification::make()
                                ->success()
                                ->title('Status updated!')
                                ->body("Church has been {$status} successfully.")
                                ->send();
                        })
                        ->visible(fn () => userCan('edit church'))
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete church')),

                    Tables\Actions\RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn () => userCan('delete church')),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate_churches')
                        ->label('✅ Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => true]));
                            
                            Notification::make()
                                ->title('Churches activated')
                                ->body("{$count} churches have been activated successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate_churches')
                        ->label('❌ Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color(Color::Red)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => false]));
                            
                            Notification::make()
                                ->title('Churches deactivated')
                                ->body("{$count} churches have been deactivated successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
                        ->color(Color::Green),
                ])->visible(fn () => userCan('delete church')),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->searchPlaceholder('🔍 Search churches by name...')
            ->emptyStateHeading('No churches found')
            ->emptyStateDescription('Start by adding your first church to the system.')
            ->emptyStateIcon('heroicon-o-building-library')
            ->recordClasses(fn ($record) => match (true) {
                !$record->is_active => 'bg-red-50 border-l-4 border-red-400',
                $record->trashed() => 'bg-gray-50 border-l-4 border-gray-400',
                default => null,
            });
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
            'index' => Pages\ListChurches::route('/'),
            'create' => Pages\CreateChurch::route('/create'),
            'view' => Pages\ViewChurch::route('/{record}'),
            'edit' => Pages\EditChurch::route('/{record}/edit'),
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
        return userCan('viewAny church');
    }
}
