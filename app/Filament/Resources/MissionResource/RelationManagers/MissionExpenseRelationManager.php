<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use App\Jobs\MissionExpense\GenerateSummaryJob;
use App\Models\MissionExpense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MissionExpenseRelationManager extends RelationManager
{
    protected static string $relationship = 'missionExpense';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount_received')
                    ->required()
                    ->numeric()
                    ->maxLength(255),

                Forms\Components\TextInput::make('token_amount')
                    ->label('Token given by the school')
                    ->default(0)
                    ->numeric(),

                Forms\Components\TextInput::make('amount_refunded')
                    ->default(0)
                    ->numeric(),
                Forms\Components\Checkbox::make('is_refunded'),
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('amount_spent')
                            ->label('Total Amount Spent')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('balance')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('amount_to_refund')
                            ->numeric()
                            ->disabled(),
                        Forms\Components\TextInput::make('refund_charge')
                            ->numeric()
                            ->disabled(),
                    ])->columns(4),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount_received')
            ->columns([
                Tables\Columns\TextColumn::make('amount_received'),
                Tables\Columns\TextColumn::make('amount_spent'),
                Tables\Columns\TextColumn::make('balance'),
                Tables\Columns\TextColumn::make('token_amount'),
                Tables\Columns\TextColumn::make('amount_to_refund'),
                Tables\Columns\TextColumn::make('amount_refunded'),
                Tables\Columns\TextColumn::make('refund_charge'),
                Tables\Columns\IconColumn::make('is_refunded')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function () {
                        GenerateSummaryJob::dispatch(MissionExpense::where('mission_id', $this->getOwnerRecord()->getKey())->first());
                    })
                    ->visible(fn () => MissionExpense::where('mission_id', $this->getOwnerRecord()->getKey())->doesntExist()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->after(function () {
                        GenerateSummaryJob::dispatch(MissionExpense::where('mission_id', $this->getOwnerRecord()->getKey())->first());
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
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
