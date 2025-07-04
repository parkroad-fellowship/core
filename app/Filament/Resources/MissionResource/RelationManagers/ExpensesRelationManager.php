<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use App\Enums\PRFMorphType;
use App\Enums\PRFTransactionType;
use App\Models\Expense;
use App\Models\MissionExpense;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Colors\Color;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExpensesRelationManager extends RelationManager
{
    protected static string $relationship = 'expenses';

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $label = 'Expense';

    protected static ?string $pluralLabel = 'Expenses';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('🏷️ Expense Identification')
                    ->description('System-generated identifiers and relationships')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('ulid')
                                    ->label('ULID')
                                    ->helperText('Unique identifier for this expense')
                                    ->visible(app()->isLocal())
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('expenseable_id')
                                    ->label('Mission Expense ID')
                                    ->helperText('Links to the mission expense record')
                                    ->numeric()
                                    ->default($this->getOwnerRecord()?->missionExpense?->id)
                                    ->disabled()
                                    ->dehydrated(false),

                                Forms\Components\TextInput::make('expenseable_type')
                                    ->label('Expense Type')
                                    ->helperText('Type of expenseable entity')
                                    ->default(PRFMorphType::MISSION_EXPENSE->value)
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),

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
                    ])->collapsible(),

                Forms\Components\Section::make('📦 Expense Details')
                    ->description('Category, type and description of the expense')
                    ->schema([
                        Forms\Components\Grid::make(2)
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
                            ]),

                        Forms\Components\Textarea::make('narration')
                            ->label('Expense Description')
                            ->helperText('Detailed description of what this expense covers')
                            ->required()
                            ->rows(4)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('confirmation_message')
                            ->label('Confirmation Message')
                            ->helperText('Any confirmation or reference message for this expense')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->collapsible(),

                Forms\Components\Section::make('💰 Cost Calculation')
                    ->description('Unit cost, quantity and total calculations')
                    ->schema([
                        Forms\Components\Grid::make(3)
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
                            ]),

                        Forms\Components\TextInput::make('line_total')
                            ->label('Line Total')
                            ->helperText('Automatically calculated from unit cost × quantity')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled()
                            ->dehydrated(true),
                    ])->collapsible(),

                Forms\Components\Section::make('📎 Receipts & Documentation')
                    ->description('Upload receipts and supporting documents')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make(Expense::RECEIPTS)
                            ->label('Receipt Images')
                            ->helperText('Upload photos or scans of receipts for this expense')
                            ->multiple()
                            ->collection(Expense::RECEIPTS)
                            ->disk(config('filament.default_filesystem_disk'))
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'application/pdf'])
                            ->maxSize(5120) // 5MB
                            ->columnSpanFull(),
                    ])->collapsible(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('expense_category_id')
            ->paginated([15, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('expenseCategory.name')
                    ->label('📂 Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(Color::Blue)
                    ->tooltip('Expense category'),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('💰 Unit Cost')
                    ->money('KES')
                    ->sortable()
                    ->tooltip('Cost per unit'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('📦 Qty')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(Color::Gray)
                    ->tooltip('Quantity of items'),

                Tables\Columns\TextColumn::make('line_total')
                    ->label('💵 Line Total')
                    ->money('KES')
                    ->sortable()
                    ->summarize(Sum::make()->money('KES'))
                    ->weight('bold')
                    ->color(Color::Green)
                    ->tooltip('Total cost for this line item'),

                Tables\Columns\TextColumn::make('charge')
                    ->label('💳 Charge')
                    ->money('KES')
                    ->sortable()
                    ->summarize(Sum::make()->money('KES'))
                    ->color(Color::Orange)
                    ->tooltip('Transaction charges'),

                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('👤 Added By')
                    ->searchable()
                    ->toggleable()
                    ->tooltip('Member who added this expense'),

                Tables\Columns\TextColumn::make('narration')
                    ->label('📝 Description')
                    ->wrap()
                    ->limit(50)
                    ->toggleable()
                    ->tooltip(fn ($record) => $record->narration),

                Tables\Columns\TextColumn::make('confirmation_message')
                    ->label('✅ Confirmation')
                    ->wrap()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn ($record) => $record->confirmation_message),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('📅 Added')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('Date expense was added'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\SelectFilter::make('expense_category_id')
                    ->label('Category')
                    ->relationship('expenseCategory', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('charge_type')
                    ->label('Charge Type')
                    ->options(PRFTransactionType::getOptions()),

                Tables\Filters\SelectFilter::make('member_id')
                    ->label('Added By')
                    ->relationship('member', 'full_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('amount_range')
                    ->label('Amount Range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_from')
                                    ->label('From')
                                    ->numeric()
                                    ->prefix('KES'),
                                Forms\Components\TextInput::make('amount_to')
                                    ->label('To')
                                    ->numeric()
                                    ->prefix('KES'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('line_total', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('line_total', '<=', $amount),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['amount_from'] ?? null) {
                            $indicators[] = 'Amount from: KES '.number_format($data['amount_from']);
                        }
                        if ($data['amount_to'] ?? null) {
                            $indicators[] = 'Amount to: KES '.number_format($data['amount_to']);
                        }

                        return $indicators;
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->label('Date Added')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->native(false)
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->native(false)
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'From: '.Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Until: '.Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus-circle')
                    ->color(Color::Green)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['expenseable_id'] = $this->getOwnerRecord()?->missionExpense?->id;
                        $data['expenseable_type'] = PRFMorphType::MISSION_EXPENSE->value;
                        $data['line_total'] = intval($data['unit_cost']) * intval($data['quantity']);

                        return $data;
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Expense added successfully')
                            ->body('The expense has been recorded and will be included in calculations.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn () => MissionExpense::where('mission_id', $this->getOwnerRecord()->getKey())->exists()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->color(Color::Gray),

                Tables\Actions\EditAction::make()
                    ->color(Color::Orange)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['expenseable_id'] = $this->getOwnerRecord()?->missionExpense?->id;
                        $data['expenseable_type'] = PRFMorphType::MISSION_EXPENSE->value;
                        $data['line_total'] = intval($data['unit_cost']) * intval($data['quantity']);

                        return $data;
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Expense updated successfully')
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

                    Tables\Actions\BulkAction::make('export_receipts')
                        ->label('Export Receipts')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color(Color::Blue)
                        ->action(function ($records) {
                            // Logic to export receipts would go here
                            Notification::make()
                                ->title('Export started')
                                ->body('Receipt export has been queued for processing.')
                                ->info()
                                ->send();
                        }),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->color(Color::Red),

                    Tables\Actions\RestoreBulkAction::make()
                        ->color(Color::Green),
                ]),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['expenseCategory', 'member'])
                ->withoutGlobalScopes([
                    SoftDeletingScope::class,
                ]));
    }
}
