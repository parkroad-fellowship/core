<?php

namespace App\Filament\Resources\ExpenseCategories;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\ExpenseCategories\Pages\CreateExpenseCategory;
use App\Filament\Resources\ExpenseCategories\Pages\EditExpenseCategory;
use App\Filament\Resources\ExpenseCategories\Pages\ListExpenseCategories;
use App\Filament\Resources\ExpenseCategories\Pages\ViewExpenseCategory;
use App\Models\ExpenseCategory;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ExpenseCategoryResource extends Resource
{
    protected static ?string $model = ExpenseCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?string $modelLabel = 'Expense Category';

    protected static ?string $pluralModelLabel = 'Expense Categories';

    protected static ?string $navigationTooltip = 'Manage expense categories and classifications';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Details')
                    ->description('Define the expense category to help organize and track spending')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                ContentSchema::nameField(
                                    name: 'name',
                                    label: 'Category Name',
                                    placeholder: 'e.g., Office Supplies, Travel, Utilities',
                                    helperText: 'Choose a clear name that describes this type of expense',
                                )
                                    ->prefixIcon('heroicon-o-tag'),

                                StatusSchema::enumSelect(
                                    name: 'is_active',
                                    label: 'Status',
                                    enumClass: PRFActiveStatus::class,
                                    default: PRFActiveStatus::ACTIVE->value,
                                    helperText: 'Active categories are available when recording expenses; inactive ones are hidden',
                                ),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Section::make('Additional Information')
                    ->description('Provide more context about when to use this category')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        ContentSchema::descriptionField(
                            name: 'description',
                            label: 'Description',
                            required: true,
                            placeholder: 'e.g., Includes paper, pens, printer ink, and other office supplies...',
                            helperText: 'Explain what types of expenses should be assigned to this category',
                        ),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-tag')
                    ->wrap()
                    ->tooltip('The name of this expense category'),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap()
                    ->limit(100)
                    ->tooltip(fn ($record) => $record->description),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFActiveStatus::fromValue($record->is_active)->name)
                    ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'success' : 'warning')
                    ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-pause-circle')
                    ->sortable()
                    ->tooltip(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value
                        ? 'This category is active and available for use'
                        : 'This category is inactive and hidden from selection'),

                TextColumn::make('expenses_count')
                    ->label('Expenses')
                    ->counts('expenses')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-currency-dollar')
                    ->tooltip('Total number of expenses recorded in this category'),

                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable()
                    ->tooltip('When this category was first created'),

                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('When this category was last updated'),

                TextColumn::make('deleted_at')
                    ->label('Date Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone ?? 'UTC')
                    ->sortable()
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('When this category was removed'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->native(false)
                    ->label('Show Deleted')
                    ->placeholder('Active categories only'),

                SelectFilter::make('is_active')
                    ->label('Filter by Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->native(false)
                    ->placeholder('All statuses'),

                Filter::make('with_expenses')
                    ->label('Has Expenses')
                    ->query(fn (Builder $query): Builder => $query->has('expenses'))
                    ->toggle()
                    ->indicator('In Use'),

                Filter::make('unused_categories')
                    ->label('No Expenses')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('expenses'))
                    ->toggle()
                    ->indicator('Unused'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn () => userCan('view expense category'))
                    ->tooltip('View full category details'),

                EditAction::make()
                    ->visible(fn () => userCan('edit expense category'))
                    ->tooltip('Make changes to this category')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Category updated')
                            ->body('The expense category has been saved successfully.')
                    ),

                Action::make('toggle_status')
                    ->label(fn (ExpenseCategory $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                    ->icon(fn (ExpenseCategory $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-pause-circle' : 'heroicon-o-play-circle')
                    ->color(fn (ExpenseCategory $record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'warning' : 'success')
                    ->action(function (ExpenseCategory $record) {
                        $newStatus = $record->is_active === PRFActiveStatus::ACTIVE->value
                            ? PRFActiveStatus::INACTIVE->value
                            : PRFActiveStatus::ACTIVE->value;
                        $record->update(['is_active' => $newStatus]);

                        $statusLabel = $newStatus === PRFActiveStatus::ACTIVE->value ? 'activated' : 'deactivated';
                        Notification::make()
                            ->success()
                            ->title('Status updated')
                            ->body("The expense category has been {$statusLabel} successfully.")
                            ->send();
                    })
                    ->tooltip('Change category status')
                    ->visible(fn () => userCan('edit expense category'))
                    ->requiresConfirmation()
                    ->modalHeading('Change Category Status')
                    ->modalDescription('Are you sure you want to change the status of this expense category?'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete expense category')),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete expense category')),

                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete expense category')),

                    BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play-circle')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]);
                            });

                            Notification::make()
                                ->success()
                                ->title('Categories activated')
                                ->body("{$count} expense categories have been activated successfully.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit expense category'))
                        ->requiresConfirmation(),

                    BulkAction::make('bulk_deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]);
                            });

                            Notification::make()
                                ->success()
                                ->title('Categories deactivated')
                                ->body("{$count} expense categories have been deactivated successfully.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit expense category'))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('name')
            ->striped()
            ->searchPlaceholder('Search expense categories...')
            ->emptyStateHeading('No expense categories found')
            ->emptyStateDescription('Create your first expense category to start organizing your spending records.')
            ->emptyStateIcon('heroicon-o-tag');
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
            'index' => ListExpenseCategories::route('/'),
            'create' => CreateExpenseCategory::route('/create'),
            'view' => ViewExpenseCategory::route('/{record}'),
            'edit' => EditExpenseCategory::route('/{record}/edit'),
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
        return userCan('viewAny expense category');
    }
}
