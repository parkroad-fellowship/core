<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\MaritalStatusResource\Pages;
use App\Models\MaritalStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class MaritalStatusResource extends Resource
{
    protected static ?string $model = MaritalStatus::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Marital Status';

    protected static ?string $modelLabel = 'Marital Status';

    protected static ?string $pluralModelLabel = 'Marital Statuses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('💍 Marital Status Information')
                    ->description('Define marital status options for member profiles')
                    ->icon('heroicon-o-heart')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('💍 Status Name')
                                    ->helperText('Enter the marital status name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Single, Married, Divorced, Widowed')
                                    ->live(onBlur: true)
                                    ->prefixIcon('heroicon-o-heart'),

                                Forms\Components\Select::make('is_active')
                                    ->label('📊 Status')
                                    ->helperText('Set marital status availability')
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
                    ->label('💍 Marital Status')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-heart')
                    ->tooltip('Marital status option'),

                Tables\Columns\TextColumn::make('members_count')
                    ->label('👥 Members')
                    ->counts('members')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 0 => 'gray',
                        $state <= 5 => 'warning',
                        $state <= 20 => 'info',
                        default => 'success',
                    })
                    ->icon('heroicon-o-users')
                    ->tooltip('Number of members with this status'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('📊 Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->size('lg')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->is_active ? 'Status is active' : 'Status is inactive'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Date status was created'),

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
                    ->tooltip('Date status was deleted'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('🗑️ Show Deleted')
                    ->placeholder('Active statuses only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('📊 Status Filter')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => '✅ Active Statuses',
                        PRFActiveStatus::INACTIVE->value => '❌ Inactive Statuses',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->indicator('Status'),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view marital status')),

                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit marital status'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Marital status updated!')
                                ->body('Marital status information has been updated successfully.')
                        ),

                    Tables\Actions\Action::make('toggle_status')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? Color::Red : Color::Green)
                        ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->action(function ($record) {
                            $record->update(['is_active' => ! $record->is_active]);
                            $status = $record->is_active ? 'activated' : 'deactivated';
                            Notification::make()
                                ->success()
                                ->title('Status updated!')
                                ->body("Marital status has been {$status} successfully.")
                                ->send();
                        })
                        ->visible(fn () => userCan('edit marital status'))
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete marital status')),

                    Tables\Actions\RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn () => userCan('delete marital status')),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate_statuses')
                        ->label('✅ Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => true]));

                            Notification::make()
                                ->title('Marital statuses activated')
                                ->body("{$count} marital statuses have been activated successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate_statuses')
                        ->label('❌ Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color(Color::Red)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => false]));

                            Notification::make()
                                ->title('Marital statuses deactivated')
                                ->body("{$count} marital statuses have been deactivated successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
                        ->color(Color::Green),
                ])->visible(fn () => userCan('delete marital status')),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->searchPlaceholder('🔍 Search marital statuses...')
            ->emptyStateHeading('No marital statuses found')
            ->emptyStateDescription('Start by adding your first marital status to the system.')
            ->emptyStateIcon('heroicon-o-heart')
            ->recordClasses(fn ($record) => match (true) {
                ! $record->is_active => 'bg-red-50 border-l-4 border-red-400',
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
            'index' => Pages\ListMaritalStatuses::route('/'),
            'create' => Pages\CreateMaritalStatus::route('/create'),
            'view' => Pages\ViewMaritalStatus::route('/{record}'),
            'edit' => Pages\EditMaritalStatus::route('/{record}/edit'),
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
        return userCan('viewAny marital status');
    }
}
