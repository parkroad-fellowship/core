<?php

namespace App\Filament\Resources\Requisitions\RelationManagers;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RequisitionItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'requisitionItems';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('item_name')
                            ->label('📦 Item Name')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., Office Supplies, Transport, Equipment')
                            ->helperText('Name or description of the item/expense'),

                        Select::make('expense_category_id')
                            ->label('📊 Expense Category')
                            ->relationship('expenseCategory', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Select the appropriate expense category'),
                    ]),

                Textarea::make('narration')
                    ->label('📝 Description/Narration')
                    ->rows(3)
                    ->maxLength(1000)
                    ->placeholder('Provide detailed description of this expense item, including purpose and justification...')
                    ->helperText('Detailed description of the expense')
                    ->columnSpanFull(),

                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('unit_price')
                            ->label('💰 Unit Price (KES)')
                            ->required()
                            ->numeric()
                            ->step(0.01)
                            ->suffix('KES')
                            ->helperText('Price per unit in KES')
                            ->formatStateUsing(fn (?int $state) => $state ? $state : 0)
                            ->dehydrateStateUsing(fn (?string $state) => $state ? (int) ($state) : 0)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $quantity = $get('quantity') ?? 1;
                                $unitPrice = $state ? (float) $state : 0;
                                $set('total_price', $unitPrice * $quantity);
                            }),

                        TextInput::make('quantity')
                            ->label('🔢 Quantity')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('Number of units')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                $quantity = $state ?? 1;
                                $unitPrice = $get('unit_price') ? (float) $get('unit_price') : 0;
                                $set('total_price', $unitPrice * $quantity);
                            }),

                        TextInput::make('total_price')
                            ->label('💸 Total Price (KES)')
                            ->required()
                            ->numeric()
                            ->disabled()
                            ->dehydrated()
                            ->suffix('KES')
                            ->helperText('Calculated total: Unit Price × Quantity')
                            ->formatStateUsing(fn (?int $state) => $state ? $state : 0)
                            ->dehydrateStateUsing(fn (?string $state) => $state ? (int) ($state) : 0),
                    ]),
            ])->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item_name')
            ->columns([
                TextColumn::make('item_name')
                    ->label('Item Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->icon('heroicon-m-cube')
                    ->wrap(),

                TextColumn::make('expenseCategory.name')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-m-tag')
                    ->placeholder('No category'),

                TextColumn::make('narration')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();

                        return strlen($state) > 50 ? $state : null;
                    })
                    ->wrap()
                    ->placeholder('No description')
                    ->toggleable(),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('KES')
                    ->sortable()
                    ->color('success')
                    ->icon('heroicon-m-banknotes'),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-calculator'),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->money('KES')
                    ->sortable()
                    ->weight('medium')
                    ->color('success')
                    ->icon('heroicon-m-currency-dollar'),

                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('expense_category')
                    ->label('Category')
                    ->relationship('expenseCategory', 'name')
                    ->placeholder('All Categories'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Item')
                    ->icon('heroicon-o-plus-circle')
                    ->modalHeading('Add Requisition Item')
                    ->modalDescription('Add a new expense item to this requisition')
                    ->successNotificationTitle('Item added successfully')
                    ->color('primary'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->modalHeading(fn ($record) => "Item: {$record->item_name}")
                        ->color('info'),
                    EditAction::make()
                        ->successNotificationTitle('Item updated successfully')
                        ->color('warning'),
                    DeleteAction::make()
                        ->successNotificationTitle('Item removed successfully')
                        ->color('danger'),
                ])->label('Actions')
                    ->color('primary')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->successNotificationTitle('Items deleted successfully'),
                ]),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Add First Item')
                    ->icon('heroicon-o-plus-circle'),
            ]);
    }
}
