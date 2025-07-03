<?php

namespace App\Filament\Resources;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\ContactTypeResource\Pages;
use App\Models\ContactType;
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

class ContactTypeResource extends Resource
{
    protected static ?string $model = ContactType::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationGroup = '⚙️ System Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Contact Types';

    protected static ?string $modelLabel = 'Contact Type';

    protected static ?string $pluralModelLabel = 'Contact Types';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📞 Contact Type Information')
                    ->description('Define contact method types for member communication')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('📋 Contact Type Name')
                                    ->helperText('Enter the contact method type name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Teacher, Patron, Headteacher')
                                    ->live(onBlur: true)
                                    ->prefixIcon('heroicon-o-tag'),

                                Forms\Components\Select::make('is_active')
                                    ->label('📊 Status')
                                    ->helperText('Set contact type availability status')
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
                    ->label('📞 Contact Type')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-phone')
                    ->tooltip('Contact method type'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('📊 Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->size('lg')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->is_active ? 'Contact type is active' : 'Contact type is inactive'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Date contact type was created'),

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
                    ->tooltip('Date contact type was deleted'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('🗑️ Show Deleted')
                    ->placeholder('Active contact types only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                Tables\Filters\SelectFilter::make('is_active')
                    ->label('📊 Status Filter')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => '✅ Active Types',
                        PRFActiveStatus::INACTIVE->value => '❌ Inactive Types',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->indicator('Status'),
            ], layout: Tables\Enums\FiltersLayout::AboveContentCollapsible)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view contact type')),

                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit contact type'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Contact type updated!')
                                ->body('Contact type information has been updated successfully.')
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
                                ->body("Contact type has been {$status} successfully.")
                                ->send();
                        })
                        ->visible(fn () => userCan('edit contact type'))
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete contact type')),

                    Tables\Actions\RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn () => userCan('delete contact type')),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate_types')
                        ->label('✅ Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => true]));
                            
                            Notification::make()
                                ->title('Contact types activated')
                                ->body("{$count} contact types have been activated successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate_types')
                        ->label('❌ Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color(Color::Red)
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => false]));
                            
                            Notification::make()
                                ->title('Contact types deactivated')
                                ->body("{$count} contact types have been deactivated successfully.")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
                        ->color(Color::Green),
                ])->visible(fn () => userCan('delete contact type')),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->searchPlaceholder('🔍 Search contact types...')
            ->emptyStateHeading('No contact types found')
            ->emptyStateDescription('Start by adding your first contact type to the system.')
            ->emptyStateIcon('heroicon-o-phone')
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
            'index' => Pages\ListContactTypes::route('/'),
            'create' => Pages\CreateContactType::route('/create'),
            'view' => Pages\ViewContactType::route('/{record}'),
            'edit' => Pages\EditContactType::route('/{record}/edit'),
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
        return userCan('viewAny contact type');
    }
}
