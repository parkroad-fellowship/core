<?php

namespace App\Filament\Resources\MissionResource\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class RequisitionsRelationManager extends RelationManager
{
    protected static string $relationship = 'requisitions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Requisition Details')
                    ->schema([
                        Select::make('member_id')
                            ->label('Requested By')
                            ->relationship('member', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        DatePicker::make('requisition_date')
                            ->label('Requisition Date')
                            ->required()
                            ->default(now()),

                        Select::make('requisition_desk')
                            ->label('Requisition Desk')
                            ->options(\App\Enums\PRFRequisitionDesk::getOptions())
                            ->required(),

                        Textarea::make('remarks')
                            ->label('Remarks/Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Tabs::make('Requisition Details')
                    ->tabs([
                        Tab::make('Items')
                            ->icon('heroicon-o-shopping-cart')
                            ->schema([
                                Repeater::make('requisitionItems')
                                    ->label('Requisition Items')
                                    ->relationship('requisitionItems')
                                    ->schema([
                                        Select::make('expense_category_id')
                                            ->label('Expense Category')
                                            ->relationship('expenseCategory', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        TextInput::make('item_name')
                                            ->label('Item Name')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('unit_price')
                                            ->label('Unit Price (KES)')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                $quantity = $get('quantity') ?? 1;
                                                $set('total_price', $state * $quantity);
                                            }),

                                        TextInput::make('quantity')
                                            ->label('Quantity')
                                            ->numeric()
                                            ->required()
                                            ->minValue(1)
                                            ->default(1)
                                            ->live()
                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                $unitPrice = $get('unit_price') ?? 0;
                                                $set('total_price', $unitPrice * $state);
                                            }),

                                        TextInput::make('total_price')
                                            ->label('Total Price (KES)')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->disabled()
                                            ->dehydrated(),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull()
                                    ->minItems(1)
                                    ->defaultItems(1)
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                        $data['total_price'] = ($data['unit_price'] ?? 0) * ($data['quantity'] ?? 1);

                                        return $data;
                                    }),
                            ]),

                        Tab::make('Payment Instructions')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Repeater::make('paymentInstructions')
                                    ->label('Payment Instructions')
                                    ->relationship('paymentInstructions')
                                    ->schema([
                                        Select::make('payment_method')
                                            ->label('Payment Method')
                                            ->options(\App\Enums\PRFPaymentMethod::getOptions())
                                            ->required()
                                            ->live(),

                                        TextInput::make('recipient_name')
                                            ->label('Recipient Name')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('amount')
                                            ->label('Amount (KES)')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->live()
                                            ->afterStateHydrated(function ($state, $get, $set) {
                                                $items = $get('../../requisitionItems') ?? [];
                                                $totalAmount = collect($items)->sum('total_price');
                                                if ($totalAmount > 0 && empty($state)) {
                                                    $set('amount', $totalAmount);
                                                }
                                            })
                                            ->live(onBlur: true)
                                            ->hint(function (Get $get) {
                                                $items = $get('../../requisitionItems') ?? [];
                                                $totalAmount = collect($items)->sum('total_price');

                                                return $totalAmount > 0 ? 'Total: KES '.number_format($totalAmount) : '';
                                            }),

                                        TextInput::make('reference')
                                            ->label('Reference/Description')
                                            ->maxLength(255),

                                        Grid::make()
                                            ->schema([
                                                PhoneInput::make('mpesa_phone_number')
                                                    ->label('MPESA Phone Number')
                                                    ->visible(fn ($get) => $get('payment_method') == \App\Enums\PRFPaymentMethod::MPESA->value),

                                                TextInput::make('paybill_number')
                                                    ->label('Paybill Number')
                                                    ->numeric()
                                                    ->visible(fn ($get) => $get('payment_method') == \App\Enums\PRFPaymentMethod::PAYBILL->value),

                                                TextInput::make('paybill_account_number')
                                                    ->label('Account Number')
                                                    ->maxLength(255)
                                                    ->visible(fn ($get) => $get('payment_method') == \App\Enums\PRFPaymentMethod::PAYBILL->value),

                                                TextInput::make('till_number')
                                                    ->label('Till Number')
                                                    ->numeric()
                                                    ->visible(fn ($get) => $get('payment_method') == \App\Enums\PRFPaymentMethod::TILL_NUMBER->value),
                                            ])
                                            ->columns(2),

                                        Grid::make()
                                            ->schema([
                                                TextInput::make('bank_name')
                                                    ->label('Bank Name')
                                                    ->maxLength(255)
                                                    ->visible(fn ($get) => $get('payment_method') == \App\Enums\PRFPaymentMethod::BANK_TRANSFER->value),

                                                TextInput::make('bank_account_number')
                                                    ->label('Account Number')
                                                    ->numeric()
                                                    ->visible(fn ($get) => $get('payment_method') == 2),

                                                TextInput::make('bank_account_name')
                                                    ->label('Account Holder Name')
                                                    ->maxLength(255)
                                                    ->visible(fn ($get) => $get('payment_method') == 2),

                                                TextInput::make('bank_branch')
                                                    ->label('Branch')
                                                    ->maxLength(255)
                                                    ->visible(fn ($get) => $get('payment_method') == 2),

                                                TextInput::make('bank_swift_code')
                                                    ->label('SWIFT Code')
                                                    ->maxLength(255)
                                                    ->visible(fn ($get) => $get('payment_method') == 2),
                                            ])
                                            ->columns(2)
                                            ->visible(fn ($get) => $get('payment_method') == 2),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->minItems(1)
                                    ->defaultItems(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('requisition_desk')
                    ->label('Desk')
                    ->badge()
                    ->formatStateUsing(fn ($record) => \App\Enums\PRFRequisitionDesk::fromValue((int) $record->requisition_desk)->getLabel())
                    ->color(fn ($record) => \App\Enums\PRFRequisitionDesk::fromValue((int) $record->requisition_desk)->getColor()),

                TextColumn::make('member.full_name')
                    ->label('Requested By')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('requisition_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('KES')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('KES')),

                TextColumn::make('requisition_items_count')
                    ->label('Items')
                    ->counts('requisitionItems')
                    ->badge()
                    ->color('info'),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('KES')
                    ->sortable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money('KES')),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('requisition_desk')
                    ->label('Requisition Desk')
                    ->options(\App\Enums\PRFRequisitionDesk::getFilterOptions()),

                Tables\Filters\SelectFilter::make('member')
                    ->label('Requested By')
                    ->relationship('member', 'full_name'),

                Tables\Filters\Filter::make('requisition_date')
                    ->label('Requisition Date')
                    ->form([
                        DatePicker::make('from_date'),
                        DatePicker::make('until_date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('requisition_date', '>=', $date),
                            )
                            ->when(
                                $data['until_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('requisition_date', '<=', $date),
                            );
                    }),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->icon('heroicon-o-plus')
                    ->color('primary'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
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
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
