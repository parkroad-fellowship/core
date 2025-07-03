<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use App\Jobs\MissionExpense\GenerateSummaryJob;
use App\Models\MissionExpense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MissionExpenseRelationManager extends RelationManager
{
    protected static string $relationship = 'missionExpense';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $label = 'Mission Expense';

    protected static ?string $pluralLabel = 'Mission Expenses';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('💰 Budget & Payments')
                    ->description('Manage mission budget, payments and tokens')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_received')
                                    ->label('Amount Received')
                                    ->helperText('Total amount received for the mission')
                                    ->required()
                                    ->numeric()
                                    ->prefix('KES')
                                    ->minValue(0)
                                    ->live(onBlur: true),

                                Forms\Components\TextInput::make('token_amount')
                                    ->label('Token Amount')
                                    ->helperText('Token amount given by the school')
                                    ->default(0)
                                    ->numeric()
                                    ->prefix('KES')
                                    ->minValue(0)
                                    ->live(onBlur: true),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_refunded')
                                    ->label('Amount Refunded')
                                    ->helperText('Amount already refunded')
                                    ->default(0)
                                    ->numeric()
                                    ->prefix('KES')
                                    ->minValue(0)
                                    ->live(onBlur: true),

                                Forms\Components\Toggle::make('is_refunded')
                                    ->label('Mark as Refunded')
                                    ->helperText('Toggle to mark this expense as refunded')
                                    ->live()
                                    ->onIcon('heroicon-m-check')
                                    ->offIcon('heroicon-m-x-mark'),
                            ]),
                    ])->collapsible(),

                Forms\Components\Section::make('📊 Calculated Amounts')
                    ->description('Auto-calculated amounts based on expenses')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_spent')
                                    ->label('Total Amount Spent')
                                    ->helperText('Auto-calculated from individual expenses')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('balance')
                                    ->label('Balance')
                                    ->helperText('Remaining balance after expenses')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_to_refund')
                                    ->label('Amount to Refund')
                                    ->helperText('Total amount eligible for refund')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('refund_charge')
                                    ->label('Refund Charge')
                                    ->helperText('Transaction charges for refund')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount_received')
            ->columns([
                Tables\Columns\TextColumn::make('amount_received')
                    ->label('💸 Received')
                    ->money('KES')
                    ->sortable()
                    ->tooltip('Total amount received for the mission'),

                Tables\Columns\TextColumn::make('amount_spent')
                    ->label('💰 Spent')
                    ->money('KES')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? Color::Orange : Color::Gray)
                    ->tooltip('Total amount spent on expenses'),

                Tables\Columns\TextColumn::make('balance')
                    ->label('💵 Balance')
                    ->money('KES')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state > 0 => Color::Green,
                        $state < 0 => Color::Red,
                        default => Color::Gray,
                    })
                    ->tooltip('Remaining balance'),

                Tables\Columns\TextColumn::make('token_amount')
                    ->label('🎫 Token')
                    ->money('KES')
                    ->sortable()
                    ->toggleable()
                    ->tooltip('Token amount from school'),

                Tables\Columns\TextColumn::make('amount_to_refund')
                    ->label('↩️ To Refund')
                    ->money('KES')
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Blue)
                    ->tooltip('Amount eligible for refund'),

                Tables\Columns\TextColumn::make('amount_refunded')
                    ->label('✅ Refunded')
                    ->money('KES')
                    ->sortable()
                    ->toggleable()
                    ->color(Color::Green)
                    ->tooltip('Amount already refunded'),

                Tables\Columns\TextColumn::make('refund_charge')
                    ->label('💳 Charges')
                    ->money('KES')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Transaction charges for refund'),

                Tables\Columns\IconColumn::make('is_refunded')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor(Color::Green)
                    ->falseColor(Color::Gray)
                    ->tooltip(fn ($state) => $state ? 'Refunded' : 'Not refunded'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\TernaryFilter::make('is_refunded')
                    ->label('Refund Status')
                    ->placeholder('All expenses')
                    ->trueLabel('Refunded')
                    ->falseLabel('Not refunded'),

                Tables\Filters\Filter::make('balance_range')
                    ->label('Balance Range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('balance_from')
                                    ->label('From')
                                    ->numeric()
                                    ->prefix('KES'),
                                Forms\Components\TextInput::make('balance_to')
                                    ->label('To')
                                    ->numeric()
                                    ->prefix('KES'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['balance_from'],
                                fn (Builder $query, $balance): Builder => $query->where('balance', '>=', $balance),
                            )
                            ->when(
                                $data['balance_to'],
                                fn (Builder $query, $balance): Builder => $query->where('balance', '<=', $balance),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['balance_from'] ?? null) {
                            $indicators[] = 'Balance from: KES '.number_format($data['balance_from']);
                        }
                        if ($data['balance_to'] ?? null) {
                            $indicators[] = 'Balance to: KES '.number_format($data['balance_to']);
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->after(function ($record) {
                        GenerateSummaryJob::dispatch($record);

                        Notification::make()
                            ->title('Mission expense created successfully')
                            ->body('Summary calculations have been queued for processing.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => MissionExpense::where('mission_id', $this->getOwnerRecord()->getKey())->doesntExist()),
            ])
            ->actions([
                Tables\Actions\Action::make('recalculate')
                    ->label('Recalculate')
                    ->icon('heroicon-m-arrow-path')
                    ->color(Color::Blue)
                    ->requiresConfirmation()
                    ->modalDescription('This will recalculate all expense summaries and balances.')
                    ->action(function ($record) {
                        GenerateSummaryJob::dispatch($record);

                        Notification::make()
                            ->title('Recalculation started')
                            ->body('Summary calculations have been queued for processing.')
                            ->success()
                            ->send();
                    })
                    ->tooltip('Recalculate expense summaries'),

                Tables\Actions\ViewAction::make()
                    ->color(Color::Gray),

                Tables\Actions\EditAction::make()
                    ->color(Color::Orange)
                    ->after(function ($record) {
                        GenerateSummaryJob::dispatch($record);

                        Notification::make()
                            ->title('Mission expense updated')
                            ->body('Summary calculations have been queued for processing.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\DeleteAction::make()
                    ->color(Color::Red),

                Tables\Actions\ForceDeleteAction::make()
                    ->color(Color::Red),

                Tables\Actions\RestoreAction::make()
                    ->color(Color::Green),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\BulkAction::make('mark_refunded')
                        ->label('Mark as Refunded')
                        ->icon('heroicon-o-check-circle')
                        ->color(Color::Green)
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_refunded' => true]);
                                GenerateSummaryJob::dispatch($record);
                            });

                            Notification::make()
                                ->title('Expenses marked as refunded')
                                ->body(count($records).' expenses have been marked as refunded.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
                        ->color(Color::Green),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
