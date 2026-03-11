<?php

namespace App\Filament\Resources\AccountingEvents\RelationManagers;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFPaymentMethod;
use App\Enums\PRFResponsibleDesk;
use App\Jobs\Requisition\ApproveJob;
use App\Jobs\Requisition\RecallJob;
use App\Jobs\Requisition\RejectJob;
use App\Jobs\Requisition\RequestReviewJob;
use App\Models\Requisition;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

class RequisitionsRelationManager extends RelationManager
{
    protected static string $relationship = 'requisitions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('📋 Requisition Information')
                    ->description('Enter the basic details for this requisition')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('member_id')
                                    ->label('👤 Requested By')
                                    ->relationship('member', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a member...')
                                    ->helperText('Choose the member making this requisition'),

                                DatePicker::make('requisition_date')
                                    ->label('📅 Requisition Date')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Date when this requisition was made'),

                                Select::make('responsible_desk')
                                    ->label('🏢 Responsible Desk')
                                    ->options(PRFResponsibleDesk::getOptions())
                                    ->required()
                                    ->placeholder('Select desk...')
                                    ->helperText('Department handling this requisition'),
                            ]),

                        Textarea::make('remarks')
                            ->label('📝 Remarks & Notes')
                            ->placeholder('Add any additional notes or special instructions...')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Optional: Any special instructions or notes for this requisition'),
                    ])
                    ->collapsible()
                    ->persistCollapsed('requisition-details'),

                Section::make('✅ Approval Information')
                    ->description('Approval status and approver details')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Grid::make(3)
                            ->columnSpanFull()
                            ->schema([
                                Select::make('approval_status')
                                    ->label('📊 Approval Status')
                                    ->options(PRFApprovalStatus::getOptions())
                                    ->default(PRFApprovalStatus::PENDING->value)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $status = PRFApprovalStatus::fromValue($state);
                                        if ($status->requiresApprovalDate()) {
                                            $set('approved_at', now());
                                        } else {
                                            $set('approved_at', null);
                                        }
                                    })
                                    ->helperText('Current approval status of this requisition'),

                                Select::make('appointed_approver_id')
                                    ->label('👤 Appointed Approver')
                                    ->relationship('appointedApprover', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Select designated approver...')
                                    ->helperText('Designate who should approve this requisition (maker-checker)')
                                    ->required(),

                                Select::make('approved_by')
                                    ->label('👨‍💼 Actual Approver')
                                    ->relationship('approvedBy', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Will be set automatically...')
                                    ->helperText('Member who actually approved/rejected this requisition')
                                    ->disabled()
                                    ->visible(fn ($get) => $get('approval_status') ? PRFApprovalStatus::fromValue($get('approval_status'))->requiresApprover() : false),

                                DatePicker::make('approved_at')
                                    ->label('📅 Approval Date')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Date when approval decision was made')
                                    ->visible(fn ($get) => $get('approval_status') ? PRFApprovalStatus::fromValue($get('approval_status'))->requiresApprovalDate() : false)
                                    ->disabled(),
                            ]),

                        Textarea::make('approval_notes')
                            ->label('📝 Approval Notes')
                            ->placeholder('Add notes about the approval decision...')
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('Optional: Notes from the approver about their decision')
                            ->visible(fn ($get) => $get('approval_status') ? PRFApprovalStatus::fromValue($get('approval_status'))->requiresApprover() : false),
                    ])
                    ->collapsible()
                    ->persistCollapsed('approval-details')
                    ->collapsed(),

                Tabs::make('Requisition Content')
                    ->tabs([
                        Tab::make('🛒 Items & Products')
                            ->icon('heroicon-o-shopping-cart')
                            ->badge(fn ($get) => count($get('requisitionItems') ?? []))
                            ->schema([
                                Section::make('Items List')
                                    ->description('Add all items needed for this requisition')
                                    ->schema([
                                        Repeater::make('requisitionItems')
                                            ->label('Requisition Items')
                                            ->relationship('requisitionItems')
                                            ->schema([
                                                Grid::make(6)
                                                    ->columnSpanFull()
                                                    ->schema([
                                                        Select::make('expense_category_id')
                                                            ->label('📂 Category')
                                                            ->relationship('expenseCategory', 'name')
                                                            ->searchable()
                                                            ->preload()
                                                            ->required()
                                                            ->placeholder('Select category...')
                                                            ->columnSpan(2),

                                                        TextInput::make('item_name')
                                                            ->label('📦 Item Name')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->placeholder('Enter item description...')
                                                            ->columnSpan(2),

                                                        TextInput::make('unit_price')
                                                            ->label('💰 Unit Price')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0)
                                                            ->prefix('KES')
                                                            ->placeholder('0.00')
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                                $quantity = $get('quantity') ?? 1;
                                                                $set('total_price', $state * $quantity);
                                                            })
                                                            ->columnSpan(1),

                                                        TextInput::make('quantity')
                                                            ->label('📊 Qty')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(1)
                                                            ->default(1)
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, $get, $set) {
                                                                $unitPrice = $get('unit_price') ?? 0;
                                                                $set('total_price', $unitPrice * $state);
                                                            })
                                                            ->columnSpan(1),
                                                    ]),

                                                TextInput::make('total_price')
                                                    ->label('💵 Total Amount')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0)
                                                    ->prefix('KES')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->extraAttributes(['class' => 'font-bold text-lg'])
                                                    ->columnSpanFull(),
                                            ])
                                            ->itemLabel(fn (array $state): ?string => $state['item_name'] ?? 'New Item')
                                            ->collapsed()
                                            ->cloneable()
                                            ->reorderable()
                                            ->columnSpanFull()
                                            ->minItems(1)
                                            ->defaultItems(1)
                                            ->addActionLabel('➕ Add Another Item')
                                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                                $data['total_price'] = ($data['unit_price'] ?? 0) * ($data['quantity'] ?? 1);

                                                return $data;
                                            }),
                                    ]),
                            ]),

                        Tab::make('💳 Payment Instructions')
                            ->icon('heroicon-o-credit-card')
                            ->badge(fn ($get) => count($get('paymentInstruction') ?? []))
                            ->schema([
                                Section::make('Payment Details')
                                    ->description('Specify how payments should be made for this requisition')
                                    ->schema([
                                        Repeater::make('paymentInstruction')
                                            ->label('Payment Instructions')
                                            ->relationship('paymentInstruction')
                                            ->schema([
                                                Grid::make(3)
                                                    ->columnSpanFull()
                                                    ->schema([
                                                        Select::make('payment_method')
                                                            ->label('💳 Payment Method')
                                                            ->options(PRFPaymentMethod::getOptions())
                                                            ->required()
                                                            ->live()
                                                            ->placeholder('Choose payment method...')
                                                            ->columnSpan(1),

                                                        TextInput::make('recipient_name')
                                                            ->label('👤 Recipient Name')
                                                            ->required()
                                                            ->maxLength(255)
                                                            ->placeholder('Enter recipient full name...')
                                                            ->columnSpan(1),

                                                        TextInput::make('amount')
                                                            ->label('💰 Amount')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0)
                                                            ->prefix('KES')
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

                                                                return $totalAmount > 0 ? '📊 Items Total: KES '.number_format($totalAmount) : '';
                                                            })
                                                            ->columnSpan(1),
                                                    ]),

                                                TextInput::make('reference')
                                                    ->label('📝 Reference/Description')
                                                    ->placeholder('Payment reference or description...')
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),

                                                // MPESA Payment Details
                                                Grid::make(2)
                                                    ->columnSpanFull()
                                                    ->schema([
                                                        PhoneInput::make('mpesa_phone_number')
                                                            ->label('📱 MPESA Phone Number')
                                                            ->placeholder('+254 7XX XXX XXX')
                                                            ->helperText('Enter the MPESA registered phone number')
                                                            ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::MPESA->value)
                                                            ->columnSpan(2),
                                                    ])
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::MPESA->value),

                                                // Paybill Payment Details
                                                Grid::make(2)
                                                    ->columnSpanFull()
                                                    ->schema([
                                                        TextInput::make('paybill_number')
                                                            ->label('🏪 Paybill Number')
                                                            ->numeric()
                                                            ->placeholder('Enter paybill number')
                                                            ->columnSpan(1),

                                                        TextInput::make('paybill_account_number')
                                                            ->label('🔢 Account Number')
                                                            ->maxLength(255)
                                                            ->placeholder('Enter account number')
                                                            ->columnSpan(1),
                                                    ])
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::PAYBILL->value),

                                                // Till Number Payment Details
                                                Grid::make(1)
                                                    ->columnSpanFull()
                                                    ->schema([
                                                        TextInput::make('till_number')
                                                            ->label('🏪 Till Number')
                                                            ->numeric()
                                                            ->placeholder('Enter till number')
                                                            ->helperText('Business till number for payment'),
                                                    ])
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::TILL_NUMBER->value),

                                                // Bank Transfer Details
                                                Grid::make(2)
                                                    ->columnSpanFull()
                                                    ->schema([
                                                        TextInput::make('bank_name')
                                                            ->label('🏦 Bank Name')
                                                            ->maxLength(255)
                                                            ->placeholder('Enter bank name')
                                                            ->columnSpan(1),

                                                        TextInput::make('bank_account_number')
                                                            ->label('🔢 Account Number')
                                                            ->numeric()
                                                            ->placeholder('Enter account number')
                                                            ->columnSpan(1),

                                                        TextInput::make('bank_account_name')
                                                            ->label('👤 Account Holder Name')
                                                            ->maxLength(255)
                                                            ->placeholder('Enter account holder name')
                                                            ->columnSpan(1),

                                                        TextInput::make('bank_branch')
                                                            ->label('🏢 Branch')
                                                            ->maxLength(255)
                                                            ->placeholder('Enter branch name')
                                                            ->columnSpan(1),

                                                        TextInput::make('bank_swift_code')
                                                            ->label('🌐 SWIFT Code')
                                                            ->maxLength(255)
                                                            ->placeholder('Enter SWIFT code (if international)')
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->visible(fn ($get) => $get('payment_method') == PRFPaymentMethod::BANK_TRANSFER->value),
                                            ])
                                            ->itemLabel(
                                                fn (array $state): ?string => ($state['recipient_name'] ?? 'New Payment').
                                                    (isset($state['amount']) ? ' - KES '.number_format($state['amount']) : '')
                                            )
                                            ->collapsed()
                                            ->cloneable()
                                            ->reorderable()
                                            ->columnSpanFull()
                                            ->minItems(1)
                                            ->defaultItems(1)
                                            ->addActionLabel('➕ Add Payment Method'),
                                    ]),
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
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('responsible_desk')
                    ->label('🏢 Desk')
                    ->badge()
                    ->formatStateUsing(fn ($record) => PRFResponsibleDesk::fromValue((int) $record->responsible_desk)->getLabel())
                    ->color(fn ($record) => PRFResponsibleDesk::fromValue((int) $record->responsible_desk)->getColor())
                    ->sortable(),

                TextColumn::make('member.full_name')
                    ->label('👤 Requested By')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('requisition_date')
                    ->label('📅 Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->description(fn ($record): string => $record->requisition_date->diffForHumans()),

                TextColumn::make('requisition_items_count')
                    ->label('📦 Items')
                    ->counts('requisitionItems')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('total_amount')
                    ->label('💰 Total Amount')
                    ->money('KES')
                    ->sortable()
                    ->weight('bold')
                    ->color('success')
                    ->summarize(Sum::make()->money('KES')->label('Grand Total'))
                    ->alignEnd(),

                TextColumn::make('approval_status')
                    ->label('✅ Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? PRFApprovalStatus::fromValue($state)->getLabel() : 'Unknown')
                    ->color(fn ($state): string => $state ? PRFApprovalStatus::fromValue($state)->getColor() : 'gray')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('appointedApprover.full_name')
                    ->label('👤 Appointed Approver')
                    ->description('Designated approver for this requisition')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Not assigned')
                    ->toggleable(),

                TextColumn::make('approvedBy.full_name')
                    ->label('👨‍💼 Actual Approver')
                    ->description(fn ($record): string => $record->approvedBy ? 'Actually approved by '.$record->approvedBy->full_name : 'Not yet approved')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('approved_at')
                    ->label('📅 Approved On')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('—')
                    ->description(fn ($record): ?string => $record->approved_at?->diffForHumans())
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('remarks')
                    ->label('📝 Notes')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('⏰ Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description(fn ($record): string => $record->created_at->diffForHumans()),

                TextColumn::make('updated_at')
                    ->label('📝 Last Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->description(fn ($record): string => $record->updated_at->diffForHumans()),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('responsible_desk')
                    ->label('🏢 Desk')
                    ->options(PRFResponsibleDesk::getFilterOptions())
                    ->multiple()
                    ->placeholder('All Desks'),

                SelectFilter::make('member')
                    ->label('👤 Requested By')
                    ->relationship('member', 'full_name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Members'),

                SelectFilter::make('approval_status')
                    ->label('✅ Approval Status')
                    ->options(PRFApprovalStatus::getOptions())
                    ->multiple()
                    ->placeholder('All Statuses'),

                SelectFilter::make('appointed_approver_id')
                    ->label('👤 Appointed Approver')
                    ->relationship('appointedApprover', 'full_name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Appointed Approvers'),

                SelectFilter::make('approved_by')
                    ->label('👨‍💼 Actual Approver')
                    ->relationship('approvedBy', 'full_name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Actual Approvers'),

                Filter::make('requisition_date')
                    ->label('📅 Date Range')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                DatePicker::make('from_date')
                                    ->label('From Date')
                                    ->placeholder('Select start date')
                                    ->native(false),
                                DatePicker::make('until_date')
                                    ->label('Until Date')
                                    ->placeholder('Select end date')
                                    ->native(false),
                            ]),
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
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from_date'] ?? null) {
                            $indicators['from_date'] = 'From: '.Carbon::parse($data['from_date'])->toFormattedDateString();
                        }
                        if ($data['until_date'] ?? null) {
                            $indicators['until_date'] = 'Until: '.Carbon::parse($data['until_date'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Filter::make('amount_range')
                    ->label('💰 Amount Range')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                TextInput::make('min_amount')
                                    ->label('Minimum Amount')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->placeholder('0'),
                                TextInput::make('max_amount')
                                    ->label('Maximum Amount')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->placeholder('No limit'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '<=', $amount),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min_amount'] ?? null) {
                            $indicators['min_amount'] = 'Min: KES '.number_format($data['min_amount']);
                        }
                        if ($data['max_amount'] ?? null) {
                            $indicators['max_amount'] = 'Max: KES '.number_format($data['max_amount']);
                        }

                        return $indicators;
                    }),

                TrashedFilter::make()
                    ->label('🗑️ Deleted Records'),
            ])
            ->filtersFormColumns(2)
            ->headerActions([
                CreateAction::make()
                    ->label('New Requisition')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->size('md'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->icon('heroicon-o-eye')
                        ->color('info'),
                    EditAction::make()
                        ->icon('heroicon-o-pencil-square')
                        ->color('warning')
                        ->visible(fn (Requisition $record) => $record->approval_status === null || $record->approval_status === PRFApprovalStatus::PENDING->value),
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Requisition')
                        ->modalDescription('Are you sure you want to approve this requisition?')
                        ->modalSubmitActionLabel('Yes, approve it')
                        ->schema([
                            Textarea::make('approval_notes')
                                ->label('Approval Notes')
                                ->placeholder('Add any notes about this approval...')
                                ->rows(3),
                        ])
                        ->action(function (Requisition $record, array $data): void {
                            ApproveJob::dispatchSync(
                                $record->ulid,
                                [
                                    'approval_notes' => $data['approval_notes'] ?? null,
                                ],
                                Auth::id(),
                            );
                        })
                        ->visible(function (Requisition $record) {
                            $currentUser = Auth::user();
                            $isAppointedApprover = $record->appointed_approver_id === $currentUser->member?->id;
                            $canApprove = in_array($record->approval_status, [PRFApprovalStatus::PENDING->value]);

                            return $isAppointedApprover && $canApprove;
                        }),
                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Requisition')
                        ->modalDescription('Are you sure you want to reject this requisition?')
                        ->modalSubmitActionLabel('Yes, reject it')
                        ->schema([
                            Textarea::make('approval_notes')
                                ->label('Rejection Reason')
                                ->placeholder('Please provide a reason for rejection...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (Requisition $record, array $data): void {
                            RejectJob::dispatchSync(
                                $record->ulid,
                                [
                                    'approval_notes' => $data['approval_notes'],
                                ],
                                Auth::id(),
                            );
                        })
                        ->visible(function (Requisition $record) {
                            $currentUser = Auth::user();
                            $isAppointedApprover = $record->appointed_approver_id === $currentUser->member?->id;
                            $canReject = in_array($record->approval_status, [PRFApprovalStatus::PENDING->value]);

                            return $isAppointedApprover && $canReject;
                        }),
                    Action::make('requestReview')
                        ->label('Request Review')
                        ->icon('heroicon-m-eye')
                        ->color('info')
                        ->visible(fn (Requisition $record) => userCan('request review requisition') &&
                            $record->approval_status === PRFApprovalStatus::PENDING->value &&
                            $record->appointed_approver_id
                        )
                        ->requiresConfirmation()
                        ->modalHeading('Request Review')
                        ->modalDescription(fn (Requisition $record) => "This will send a review request to {$record->appointedApprover?->full_name} and notify them to review this requisition.")
                        ->action(function (Requisition $record): void {
                            if ($record->requisitionItems()->doesntExist()) {
                                Notification::make()
                                    ->title('Cannot request review')
                                    ->body('A requisition must have at least one line item.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            if ($record->paymentInstruction()->doesntExist()) {
                                Notification::make()
                                    ->title('Cannot request review')
                                    ->body('You must provide a payment instruction for this requisition.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            RequestReviewJob::dispatchSync(
                                $record->ulid,
                                [
                                    'appointed_approver_ulid' => $record->appointedApprover->ulid,
                                ],
                            );
                        })
                        ->successNotificationTitle('Review requested successfully'),
                    Action::make('recall')
                        ->label('Recall')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('warning')
                        ->visible(fn (Requisition $record) => userCan('recall requisition') && $record->canBeRecalled())
                        ->requiresConfirmation()
                        ->modalHeading('Recall Requisition')
                        ->modalDescription(fn (Requisition $record) => "Are you sure you want to recall requisition {$record->ulid}? All approvers and desk members will be notified not to take any action on this requisition.")
                        ->action(function (Requisition $record): void {
                            RecallJob::dispatchSync(
                                $record->ulid,
                                [
                                    'approval_notes' => 'Requisition recalled by requester',
                                ],
                                Auth::id(),
                            );
                        })
                        ->successNotificationTitle('Requisition recalled successfully'),
                    DeleteAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Requisition')
                        ->modalDescription('Are you sure you want to delete this requisition? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete it')
                        ->visible(fn (Requisition $record) => $record->approval_status === null || $record->approval_status === PRFApprovalStatus::PENDING->value),
                    ForceDeleteAction::make()
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Requisition')
                        ->modalDescription('Are you sure you want to permanently delete this requisition? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete permanently'),
                    RestoreAction::make()
                        ->icon('heroicon-o-arrow-path')
                        ->color('success'),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
                    ->button(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Requisitions')
                        ->modalDescription('Are you sure you want to delete the selected requisitions? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete them'),
                    ForceDeleteBulkAction::make()
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Selected Requisitions')
                        ->modalDescription('Are you sure you want to permanently delete the selected requisitions? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete permanently'),
                    RestoreBulkAction::make()
                        ->icon('heroicon-o-arrow-path')
                        ->color('success'),
                ])
                    ->label('Bulk Actions')
                    ->color('gray'),
            ])
            ->emptyStateActions([
                CreateAction::make()
                    ->label('Create your first requisition')
                    ->icon('heroicon-o-plus')
                    ->color('primary'),
            ])
            ->emptyStateHeading('No requisitions yet')
            ->emptyStateDescription('Get started by creating your first requisition.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]));
    }
}
