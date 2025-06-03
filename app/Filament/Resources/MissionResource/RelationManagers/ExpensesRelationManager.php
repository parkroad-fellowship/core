<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use App\Enums\PRFMorphType;
use App\Enums\PRFTransactionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('expenseable_id')
                            ->required()
                            ->numeric()
                            ->default($this->getOwnerRecord()?->missionExpense?->id)
                            ->label('Expenseable ID (Pre-filled)')
                            ->disabled(),
                        Forms\Components\TextInput::make('expenseable_type')
                            ->required()
                            ->numeric()
                            ->default(PRFMorphType::MISSION_EXPENSE->value)
                            ->label('Expenseable Type (Pre-filled)')
                            ->disabled(),
                        Forms\Components\Select::make('member_id')
                            ->relationship(
                                name: 'member',
                                titleAttribute: 'full_name',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    ->whereHas('missionSubscriptions', fn (Builder $query) => $query
                                        ->where('mission_id', $this->ownerRecord->id)),
                            )
                            ->label('Added By'),
                    ]),
                Forms\Components\Select::make('expense_category_id')
                    ->relationship('expenseCategory', 'name')
                    ->label('Expense Category'),
                Forms\Components\Select::make('charge_type')
                    ->required()
                    ->options(PRFTransactionType::getOptions())
                    ->label('Charge Type'),
                Forms\Components\Grid::make()
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('unit_cost')
                            ->required()
                            ->numeric()
                            ->prefix('KES')
                            ->label('Unit Cost'),
                        Forms\Components\TextInput::make('quantity')
                            ->required()
                            ->numeric()
                            ->label('Quantity'),
                        Forms\Components\TextInput::make('charge')
                            ->required()
                            ->numeric()
                            ->prefix('KES')
                            ->label('Charge'),
                    ]),
                Forms\Components\Textarea::make('narration')
                    ->required()
                    ->rows(6)
                    ->label('Narration'),
                Forms\Components\Textarea::make('confirmation_message')
                    ->required()
                    ->rows(6)
                    ->label('Confirmation Message'),
                Forms\Components\TextInput::make('line_total')
                    ->numeric()
                    ->prefix('KES')
                    ->label('Line Total (Auto-calculated)')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('expense_category_id')
            ->paginated([15])
            ->columns([
                Tables\Columns\TextColumn::make('expenseCategory.name')
                    ->label('Expense Category'),
                Tables\Columns\TextColumn::make('unit_cost')
                    ->wrap()
                    ->label('Unit Cost'),
                Tables\Columns\TextColumn::make('quantity')
                    ->wrap()
                    ->label('Quantity'),
                Tables\Columns\TextColumn::make('line_total')
                    ->wrap()
                    ->label('Line Total')
                    ->summarize(Sum::make()),
                Tables\Columns\TextColumn::make('charge')
                    ->wrap()
                    ->label('Transaction Charge')
                    ->summarize(Sum::make()),
                Tables\Columns\TextColumn::make('narration')
                    ->wrap()
                    ->label('Narration'),
                Tables\Columns\TextColumn::make('confirmation_message')
                    ->wrap()
                    ->label('Confirmation Message'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {

                        $data['expenseable_id'] = $this->getOwnerRecord()?->missionExpense?->id;
                        $data['expenseable_type'] = PRFMorphType::MISSION_EXPENSE->value;
                        $data['line_total'] = intval($data['unit_cost']) * intval($data['quantity']);

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {

                        $data['expenseable_id'] = $this->getOwnerRecord()?->missionExpense?->id;
                        $data['expenseable_type'] = PRFMorphType::MISSION_EXPENSE->value;
                        $data['line_total'] = intval($data['unit_cost']) * intval($data['quantity']);

                        return $data;
                    }),
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
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['expenseCategory'])
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
