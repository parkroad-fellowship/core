<?php

namespace App\Filament\Resources\SchoolResource\RelationManagers;

use App\Enums\PRFActiveStatus;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BudgetEstimatesRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetEstimates';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('📊 Budget Estimate')
                    ->description('Define the estimated costs for this school.')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Grid::make(12)
                            ->schema([
                                Select::make('is_active')
                                    ->label('Status')
                                    ->options(PRFActiveStatus::getOptions())
                                    ->native(false)
                                    ->hiddenOn('create')
                                    ->default(PRFActiveStatus::ACTIVE)
                                    ->columnSpan(3),
                            ]),
                    ])
                    ->hiddenOn(['create', 'edit'])
                    ->collapsible()
                    ->persistCollapsed(),

                Section::make('🧾 Estimate Items')
                    ->description('Add line items to build your budget estimate.')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->schema([
                        Repeater::make('budgetEstimateEntries')
                            ->relationship()
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('expense_category_id')
                                            ->label('Expense Category')
                                            ->relationship('expenseCategory', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->native(false),

                                        TextInput::make('item_name')
                                            ->label('Item')
                                            ->placeholder('e.g., Transport to school')
                                            ->required()
                                            ->maxLength(255),

                                        Grid::make(3)
                                            ->schema([

                                                TextInput::make('unit_price')
                                                    ->label('Unit Price (KES)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->required()
                                                    ->default(0)
                                                    ->live(debounce: 300)
                                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                        $quantity = (int) ($get('quantity') ?? 1);
                                                        $unitPrice = (int) ($state ?? 0);
                                                        $set('total_price', $unitPrice * $quantity);
                                                    })->columnSpan(1),

                                                TextInput::make('quantity')
                                                    ->label('Qty')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->required()
                                                    ->live(debounce: 300)
                                                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                                        $quantity = (int) ($state ?? 1);
                                                        $unitPrice = (int) ($get('unit_price') ?? 0);
                                                        $set('total_price', $unitPrice * $quantity);
                                                    })
                                                    ->columnSpan(1),

                                                TextInput::make('total_price')
                                                    ->label('Total Price (KES)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->required()
                                                    ->readOnly()
                                                    ->dehydrated(true)
                                                    ->default(0)
                                                    ->columnSpan(1),
                                            ]),

                                        Textarea::make('notes')
                                            ->label('Notes')
                                            ->placeholder('Additional details about this item.')
                                            ->rows(2)
                                            ->columnSpanFull(),

                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('budget_estimate_entries_count')
                    ->label('📦 Items')
                    ->counts('budgetEstimateEntries')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-shopping-cart'),
                TextColumn::make('grand_total')
                    ->label('💰 Total')
                    ->state(fn ($record) => (int) $record->budgetEstimateEntries()->sum('total_price'))
                    ->money('KES')
                    ->sortable()
                    ->summarize(Sum::make()->money('KES')->label('Total'))
                    ->weight('bold')
                    ->icon('heroicon-o-banknotes'),
                TextColumn::make('created_at')
                    ->label('🕒 Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('New Budget Estimate')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->size('md')
                    ->visible(fn () => $this->ownerRecord->budgetEstimates()->count() === 0),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
