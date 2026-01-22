<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\ContactTypeResource\Pages\ListContactTypes;
use App\Filament\Resources\ContactTypeResource\Pages\CreateContactType;
use App\Filament\Resources\ContactTypeResource\Pages\ViewContactType;
use App\Filament\Resources\ContactTypeResource\Pages\EditContactType;
use App\Enums\PRFActiveStatus;
use App\Filament\Resources\ContactTypeResource\Pages;
use App\Models\ContactType;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ContactTypeResource extends Resource
{
    protected static ?string $model = ContactType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-phone';

    protected static string | \UnitEnum | null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Contact Types';

    protected static ?string $modelLabel = 'Contact Type';

    protected static ?string $pluralModelLabel = 'Contact Types';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('📞 Contact Type Information')
                    ->description('Define contact method types for member communication')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('📋 Contact Type Name')
                                    ->helperText('Enter the contact method type name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g., Teacher, Patron, Headteacher')
                                    ->live(onBlur: true)
                                    ->prefixIcon('heroicon-o-tag'),

                                Select::make('is_active')
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
                TextColumn::make('name')
                    ->label('📞 Contact Type')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-phone')
                    ->tooltip('Contact method type'),

                IconColumn::make('is_active')
                    ->label('📊 Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->size('lg')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->is_active ? 'Contact type is active' : 'Contact type is inactive'),

                TextColumn::make('created_at')
                    ->label('📅 Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Date contact type was created'),

                TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Last modification date'),

                TextColumn::make('deleted_at')
                    ->label('🗑️ Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Red)
                    ->tooltip('Date contact type was deleted'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('🗑️ Show Deleted')
                    ->placeholder('Active contact types only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                SelectFilter::make('is_active')
                    ->label('📊 Status Filter')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => '✅ Active Types',
                        PRFActiveStatus::INACTIVE->value => '❌ Inactive Types',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->indicator('Status'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view contact type')),

                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit contact type'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Contact type updated!')
                                ->body('Contact type information has been updated successfully.')
                        ),

                    Action::make('toggle_status')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? Color::Red : Color::Green)
                        ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->action(function ($record) {
                            $record->update(['is_active' => ! $record->is_active]);
                            $status = $record->is_active ? 'activated' : 'deactivated';
                            Notification::make()
                                ->success()
                                ->title('Status updated!')
                                ->body("Contact type has been {$status} successfully.")
                                ->send();
                        })
                        ->visible(fn () => userCan('edit contact type'))
                        ->requiresConfirmation(),

                    DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete contact type')),

                    RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn () => userCan('delete contact type')),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate_types')
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

                    BulkAction::make('deactivate_types')
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

                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    RestoreBulkAction::make()
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
            'index' => ListContactTypes::route('/'),
            'create' => CreateContactType::route('/create'),
            'view' => ViewContactType::route('/{record}'),
            'edit' => EditContactType::route('/{record}/edit'),
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
