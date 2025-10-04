<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use App\Enums\PRFMorphType;
use App\Enums\PRFTransactionType;
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
                Forms\Components\Tabs::make('Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('💰 Budget Overview')
                            ->schema([
                                Forms\Components\Section::make('💰 Budget & Payments')
                                    ->description('Manage mission budget, payments and tokens')
                                    ->schema([
                                        Forms\Components\Grid::make(4)
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
                                        Forms\Components\Grid::make(4)
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
                            ]),

                        Forms\Components\Tabs\Tab::make('📊 Expense Items')
                            ->schema([
                                Forms\Components\Repeater::make('expenses')
                                    ->relationship('expenses')
                                    ->label('Expense Items')
                                    ->schema([
                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\Select::make('expense_category_id')
                                                    ->label('Expense Category')
                                                    ->helperText('Select the appropriate expense category')
                                                    ->relationship('expenseCategory', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->required(),

                                                Forms\Components\Select::make('charge_type')
                                                    ->label('Charge Type')
                                                    ->helperText('Select how this expense is charged')
                                                    ->options(PRFTransactionType::getOptions())
                                                    ->required()
                                                    ->live(),

                                                Forms\Components\Select::make('member_id')
                                                    ->label('Added By')
                                                    ->helperText('Mission member who added this expense')
                                                    ->relationship(
                                                        name: 'member',
                                                        titleAttribute: 'full_name',
                                                        modifyQueryUsing: fn (Builder $query) => $query
                                                            ->whereHas('missionSubscriptions', fn (Builder $query) => $query
                                                                ->where('mission_id', $this->ownerRecord->id)),
                                                    )
                                                    ->searchable()
                                                    ->preload(),
                                            ]),

                                        Forms\Components\Textarea::make('narration')
                                            ->label('Expense Description')
                                            ->helperText('Detailed description of what this expense covers')
                                            ->required()
                                            ->rows(3)
                                            ->columnSpanFull(),

                                        Forms\Components\Textarea::make('confirmation_message')
                                            ->label('Confirmation Message')
                                            ->helperText('Any confirmation or reference message for this expense')
                                            ->required()
                                            ->rows(2)
                                            ->columnSpanFull(),

                                        Forms\Components\Grid::make(4)
                                            ->schema([
                                                Forms\Components\TextInput::make('unit_cost')
                                                    ->label('Unit Cost')
                                                    ->helperText('Cost per individual item')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                        $quantity = $get('quantity') ?? 1;
                                                        $set('line_total', $state * $quantity);
                                                    }),

                                                Forms\Components\TextInput::make('quantity')
                                                    ->label('Quantity')
                                                    ->helperText('Number of items')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                        $unitCost = $get('unit_cost') ?? 0;
                                                        $set('line_total', $state * $unitCost);
                                                    }),

                                                Forms\Components\TextInput::make('charge')
                                                    ->label('Transaction Charge')
                                                    ->helperText('Additional transaction fees')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->minValue(0)
                                                    ->default(0),

                                                Forms\Components\TextInput::make('line_total')
                                                    ->label('Line Total')
                                                    ->helperText('Automatically calculated from unit cost × quantity')
                                                    ->numeric()
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated(true),
                                            ]),

                                        Forms\Components\TextInput::make('line_total')
                                            ->label('Line Total')
                                            ->helperText('Automatically calculated from unit cost × quantity')
                                            ->numeric()
                                            ->prefix('KES')
                                            ->disabled()
                                            ->dehydrated(true),

                                        Forms\Components\SpatieMediaLibraryFileUpload::make('receipts')
                                            ->label('Receipt Images')
                                            ->helperText('Upload photos or scans of receipts for this expense')
                                            ->multiple()
                                            ->collection('receipts')
                                            ->disk(config('filament.default_filesystem_disk'))
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'application/pdf'])
                                            ->maxSize(5120)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(1)
                                    ->itemLabel(fn (array $state): ?string => $state['narration'] ?? null)
                                    ->defaultItems(0)
                                    ->addActionLabel('Add Expense Item')
                                    ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                        $data['expenseable_id'] = $this->getOwnerRecord()?->missionExpense?->id;
                                        $data['expenseable_type'] = PRFMorphType::MISSION_EXPENSE->value;
                                        $data['line_total'] = intval($data['unit_cost']) * intval($data['quantity']);

                                        return $data;
                                    })
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                        $data['expenseable_id'] = $this->getOwnerRecord()?->missionExpense?->id;
                                        $data['expenseable_type'] = PRFMorphType::MISSION_EXPENSE->value;
                                        $data['line_total'] = intval($data['unit_cost']) * intval($data['quantity']);

                                        return $data;
                                    }),
                            ]),
                    ]),
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
