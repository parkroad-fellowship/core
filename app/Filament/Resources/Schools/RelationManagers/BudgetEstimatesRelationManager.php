<?php

namespace App\Filament\Resources\Schools\RelationManagers;

use App\Enums\PRFActiveStatus;
use App\Models\MissionType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BudgetEstimatesRelationManager extends RelationManager
{
    protected static string $relationship = 'budgetEstimates';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('📊 Budget Estimate')
                    ->columnSpanFull()
                    ->description('Define the estimated costs for this school and mission type.')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Grid::make(12)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('mission_type_id')
                                    ->label('Mission Type')
                                    ->options(fn () => MissionType::query()
                                        ->where('is_active', PRFActiveStatus::ACTIVE)
                                        ->orderBy('name')
                                        ->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->required()
                                    ->disableOptionWhen(function ($value, $record, RelationManager $livewire) {
                                        // Only one active estimate per school + mission type
                                        if ($record && intval($record->mission_type_id) === intval($value)) {
                                            return false;
                                        }

                                        return $livewire->ownerRecord->budgetEstimates()
                                            ->where('is_active', PRFActiveStatus::ACTIVE)
                                            ->whereNull('deleted_at')
                                            ->where('mission_type_id', $value)
                                            ->exists();
                                    })
                                    ->helperText('The requisition for this type of mission will be based on this estimate.')
                                    ->columnSpan(4),

                                TextInput::make('baseline_people')
                                    ->label('Avg Team Size')
                                    ->numeric()
                                    ->minValue(1)
                                    ->default(1)
                                    ->required()
                                    ->helperText('Typical number of missionaries going. Per-person items are scaled to this.')
                                    ->columnSpan(3),

                                Select::make('is_active')
                                    ->label('Status')
                                    ->options(PRFActiveStatus::getOptions())
                                    ->native(false)
                                    ->default(PRFActiveStatus::ACTIVE->value)
                                    ->columnSpan(3),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Section::make('🧾 Estimate Items')
                    ->columnSpanFull()
                    ->description('Add line items to build your budget estimate.')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('budgetEstimateEntries')
                            ->relationship()
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->schema([
                                Grid::make(2)
                                    ->columnSpanFull()
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
                                            ->columnSpanFull()
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
                TextColumn::make('missionType.name')
                    ->label('🎯 Mission Type')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-flag')
                    ->sortable(),
                TextColumn::make('baseline_people')
                    ->label('👥 Avg Team')
                    ->numeric()
                    ->sortable()
                    ->tooltip('Baseline team size this estimate was built for'),
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
                TextColumn::make('is_active')
                    ->label('Status')
                    ->formatStateUsing(fn ($state) => $state->getLabel())
                    ->badge()
                    ->color(fn ($state) => $state->getColor()),
                TextColumn::make('created_at')
                    ->label('🕒 Created')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('mission_type_id')
                    ->label('Mission Type')
                    ->options(fn () => MissionType::query()->orderBy('name')->pluck('name', 'id'))
                    ->native(false),
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New Budget Estimate')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->size('md'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
