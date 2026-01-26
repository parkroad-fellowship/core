<?php

namespace App\Filament\Resources\Churches;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContactSchema;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\Churches\Pages\CreateChurch;
use App\Filament\Resources\Churches\Pages\EditChurch;
use App\Filament\Resources\Churches\Pages\ListChurches;
use App\Filament\Resources\Churches\Pages\ViewChurch;
use App\Models\Church;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ChurchResource extends Resource
{
    protected static ?string $model = Church::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-library';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Churches';

    protected static ?string $modelLabel = 'Church';

    protected static ?string $pluralModelLabel = 'Churches';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Basic Church Information Section
                Section::make('Church Information')
                    ->description('Enter the basic details about this church')
                    ->icon('heroicon-o-building-library')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                ContentSchema::nameField(
                                    name: 'name',
                                    label: 'Church Name',
                                    placeholder: 'e.g., St. Paul\'s Cathedral, Grace Community Church',
                                    helperText: 'Enter the official name of the church as it is commonly known',
                                )
                                    ->prefixIcon('heroicon-o-building-library')
                                    ->live(onBlur: true),

                                StatusSchema::enumSelect(
                                    name: 'is_active',
                                    label: 'Status',
                                    enumClass: PRFActiveStatus::class,
                                    default: PRFActiveStatus::ACTIVE->value,
                                    required: true,
                                    hiddenOnCreate: true,
                                    helperText: 'Set whether this church is currently active in the system',
                                )
                                    ->suffixIcon('heroicon-o-check-circle'),
                            ]),

                        ContentSchema::descriptionField(
                            name: 'description',
                            label: 'Description',
                            rows: 3,
                            placeholder: 'Describe the church, its denomination, congregation size, and any relevant information...',
                            helperText: 'Provide helpful context about the church that team members should know',
                        ),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                // Contact Information Section
                Section::make('Contact Information')
                    ->description('Contact details for coordinating with the church')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                ContactSchema::phoneField(
                                    name: 'phone_number',
                                    label: 'Phone Number',
                                    required: false,
                                    helperText: 'Primary contact phone number for the church office',
                                ),

                                ContactSchema::emailField(
                                    name: 'email',
                                    label: 'Email Address',
                                    required: false,
                                    helperText: 'Official church email for communications',
                                ),
                            ]),

                        ContentSchema::descriptionField(
                            name: 'address',
                            label: 'Physical Address',
                            rows: 2,
                            placeholder: 'e.g., 123 Main Street, Westlands, Nairobi',
                            helperText: 'Enter the full street address of the church',
                        ),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed(),

                // Additional Notes Section
                Section::make('Additional Notes')
                    ->description('Any other important information about this church')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        ContentSchema::notesField(
                            name: 'notes',
                            label: 'Internal Notes',
                            rows: 3,
                            placeholder: 'Add any internal notes, special instructions, or reminders about this church...',
                        ),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->persistCollapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Church Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-building-library')
                    ->color(Color::Blue)
                    ->tooltip('Church name and location'),

                TextColumn::make('members_count')
                    ->label('Members')
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

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->size('lg')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->is_active ? 'Church is active' : 'Church is inactive'),

                TextColumn::make('created_at')
                    ->label('Added On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Date church was registered'),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Gray)
                    ->tooltip('Last modification date'),

                TextColumn::make('deleted_at')
                    ->label('Deleted On')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(Color::Red)
                    ->tooltip('Date church was deleted'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Show Deleted')
                    ->placeholder('Active churches only')
                    ->trueLabel('With deleted')
                    ->falseLabel('Active only'),

                SelectFilter::make('is_active')
                    ->label('Status Filter')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active Churches',
                        PRFActiveStatus::INACTIVE->value => 'Inactive Churches',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->indicator('Status'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color(Color::Gray)
                        ->visible(fn () => userCan('view church')),

                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color(Color::Orange)
                        ->visible(fn () => userCan('edit church'))
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Church updated!')
                                ->body('Church information has been updated successfully.')
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
                                ->body("Church has been {$status} successfully.")
                                ->send();
                        })
                        ->visible(fn () => userCan('edit church'))
                        ->requiresConfirmation(),

                    DeleteAction::make()
                        ->color(Color::Red)
                        ->visible(fn () => userCan('delete church')),

                    RestoreAction::make()
                        ->color(Color::Green)
                        ->visible(fn () => userCan('delete church')),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('activate_churches')
                        ->label('Activate Selected')
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

                    BulkAction::make('deactivate_churches')
                        ->label('Deactivate Selected')
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

                    DeleteBulkAction::make()
                        ->color(Color::Red),

                    ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    RestoreBulkAction::make()
                        ->color(Color::Green),
                ])->visible(fn () => userCan('delete church')),
            ])
            ->defaultSort('name', 'asc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->extremePaginationLinks()
            ->searchPlaceholder('Search churches by name...')
            ->emptyStateHeading('No churches found')
            ->emptyStateDescription('Start by adding your first church to the system.')
            ->emptyStateIcon('heroicon-o-building-library')
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
            'index' => ListChurches::route('/'),
            'create' => CreateChurch::route('/create'),
            'view' => ViewChurch::route('/{record}'),
            'edit' => EditChurch::route('/{record}/edit'),
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
