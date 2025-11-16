<?php

namespace App\Filament\Resources;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFResponsibleDesk;
use App\Filament\Resources\RequisitionResource\Pages;
use App\Filament\Resources\RequisitionResource\RelationManagers;
use App\Models\Requisition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class RequisitionResource extends Resource
{
    protected static ?string $model = Requisition::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Requisitions';

    protected static ?string $modelLabel = 'Requisition';

    protected static ?string $pluralModelLabel = 'Requisitions';

    protected static ?string $navigationGroup = 'Treasurer';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationTooltip = 'Manage expense requisitions and approvals';

    protected static int $globalSearchResultsLimit = 20;

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->ulid;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Member' => $record->member?->full_name ?? 'Unknown Member',
            'Amount' => 'KES '.number_format($record->total_amount, 2),
            'Status' => PRFApprovalStatus::from($record->approval_status)->getLabel(),
            'Date' => $record->requisition_date->format('M j, Y'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['ulid', 'member.full_name', 'remarks', 'approval_notes'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('approval_status', PRFApprovalStatus::PENDING->value)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $count = static::getNavigationBadge();

        return $count > 0 ? 'warning' : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = static::getNavigationBadge();

        return $count.' pending requisition'.($count !== 1 ? 's' : '');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->description('Essential requisition details')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('ulid')
                                    ->label('Requisition ID')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->placeholder('Auto-generated')
                                    ->helperText('Unique identifier for this requisition'),

                                Forms\Components\Select::make('member_id')
                                    ->label('👤 Requesting Member')
                                    ->relationship('member', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Member making the requisition request')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name ?? 'Unknown Member'),

                                Forms\Components\Select::make('accounting_event_id')
                                    ->label('📊 Accounting Event')
                                    ->relationship('accountingEvent', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->helperText('Budget line item for this expense')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? 'Unknown Event'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('requisition_date')
                                    ->label('📅 Requisition Date')
                                    ->required()
                                    ->default(today())
                                    ->native(false)
                                    ->helperText('Date when the requisition was made'),

                                Forms\Components\Select::make('responsible_desk')
                                    ->label('🏢 Responsible Desk')
                                    ->options(PRFResponsibleDesk::getOptions())
                                    ->required()
                                    ->helperText('Desk responsible for this requisition')
                                    ->placeholder('Select responsible desk...'),
                            ]),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Approval & Status')
                    ->description('Approval workflow and status information')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('approval_status')
                                    ->label('📋 Approval Status')
                                    ->options(PRFApprovalStatus::getOptions())
                                    ->default(PRFApprovalStatus::PENDING->value)
                                    ->required()
                                    ->live()
                                    ->helperText('Current approval status'),

                                Forms\Components\Select::make('appointed_approver_id')
                                    ->label('👥 Appointed Approver')
                                    ->relationship('appointedApprover', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Member appointed to approve this requisition')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name ?? 'Unknown Member'),

                                Forms\Components\Select::make('approved_by')
                                    ->label('✅ Approved By')
                                    ->relationship('approvedBy', 'full_name')
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Member who actually approved/rejected')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name ?? 'Unknown Member')
                                    ->visible(fn (callable $get) => in_array($get('approval_status'), [PRFApprovalStatus::APPROVED->value, PRFApprovalStatus::REJECTED->value])),
                            ]),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\DateTimePicker::make('review_requested_at')
                                    ->label('⏰ Review Requested At')
                                    ->native(false)
                                    ->helperText('When review was requested'),

                                Forms\Components\DateTimePicker::make('approved_at')
                                    ->label('✅ Approved At')
                                    ->native(false)
                                    ->helperText('When the requisition was approved')
                                    ->visible(fn (callable $get) => $get('approval_status') == PRFApprovalStatus::APPROVED->value),

                                Forms\Components\DateTimePicker::make('rejected_at')
                                    ->label('❌ Rejected At')
                                    ->native(false)
                                    ->helperText('When the requisition was rejected')
                                    ->visible(fn (callable $get) => $get('approval_status') == PRFApprovalStatus::REJECTED->value),
                            ]),

                        Forms\Components\Textarea::make('approval_notes')
                            ->label('📝 Approval Notes')
                            ->rows(3)
                            ->helperText('Notes from the approver regarding their decision')
                            ->placeholder('Enter any notes regarding the approval/rejection...')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Financial Information')
                    ->description('Amount and financial details')
                    ->icon('heroicon-o-banknotes')
                    ->schema([
                        Forms\Components\TextInput::make('total_amount')
                            ->label('💰 Total Amount (KES)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->suffix('KES')
                            ->step(0.01)
                            ->helperText('Total amount in Kenyan Shillings (will be stored as cents)')
                            ->formatStateUsing(fn (?int $state) => $state ? $state : 0)
                            ->dehydrateStateUsing(fn (?string $state) => $state ? (int) ($state) : 0),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Additional Information')
                    ->description('Comments and additional details')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        Forms\Components\Textarea::make('remarks')
                            ->label('📄 Remarks')
                            ->rows(4)
                            ->helperText('Additional comments or details about this requisition')
                            ->placeholder('Enter any additional information, justification, or special requirements...')
                            ->columnSpanFull(),
                    ])
                    ->columns(1),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('accountingEvent.name')
                    ->label('Event / Budget Line')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->wrap()
                    ->tooltip(fn (Requisition $record): ?string => $record->accountingEvent?->name)
                    ->icon('heroicon-m-chart-bar')
                    ->placeholder('No event assigned'),

                Tables\Columns\TextColumn::make('responsible_desk')
                    ->label('Responsible Desk')
                    ->formatStateUsing(fn (int $state): string => PRFResponsibleDesk::from($state)->getLabel()
                    )
                    ->badge()
                    ->color(fn (int $state): string => PRFResponsibleDesk::from($state)->getColor()
                    )
                    ->icon(fn (int $state): string => PRFResponsibleDesk::from($state)->getIcon()
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('member.full_name')
                    ->label('Requesting Member')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->description(fn (Requisition $record): ?string => $record->member?->email ?? null
                    )
                    ->placeholder('Unknown Member'),

                Tables\Columns\TextColumn::make('requisition_date')
                    ->label('Request Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->icon('heroicon-m-calendar')
                    ->description(fn (Requisition $record): ?string => $record->requisition_date?->diffForHumans()
                    ),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->money('KES')
                    ->sortable()
                    ->icon('heroicon-m-banknotes')
                    ->color('success')
                    ->weight('medium')
                    ->tooltip('Total requisition amount'),

                Tables\Columns\TextColumn::make('approval_status')
                    ->label('Status')
                    ->formatStateUsing(fn (int $state): string => PRFApprovalStatus::from($state)->getLabel()
                    )
                    ->badge()
                    ->color(fn (int $state): string => PRFApprovalStatus::from($state)->getColor()
                    )
                    ->icon(fn (int $state): string => PRFApprovalStatus::from($state)->getIcon()
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Approved Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn (Requisition $record): string => 'Created: '.$record->created_at->format('F j, Y \a\t g:i A')
                    ),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn (Requisition $record): string => 'Updated: '.$record->updated_at->format('F j, Y \a\t g:i A')
                    ),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistFiltersInSession()
            ->striped()
            ->filters([
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Records')
                    ->placeholder('All Records'),

                PRFApprovalStatus::getTableFilter(),

                PRFResponsibleDesk::getTableFilter(),

                Tables\Filters\SelectFilter::make('member')
                    ->label('Requesting Member')
                    ->relationship('member', 'full_name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Members'),

                Tables\Filters\SelectFilter::make('accountingEvent')
                    ->label('Budget Line')
                    ->relationship('accountingEvent', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Budget Lines'),

                Tables\Filters\Filter::make('amount_range')
                    ->label('Amount Range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_from')
                                    ->label('From (KES)')
                                    ->numeric()
                                    ->placeholder('0'),
                                Forms\Components\TextInput::make('amount_to')
                                    ->label('To (KES)')
                                    ->numeric()
                                    ->placeholder('1,000,000'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('total_amount', '<=', $amount),
                            );
                    }),

                Tables\Filters\Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('date_from')
                                    ->label('From Date')
                                    ->native(false),
                                Forms\Components\DatePicker::make('date_to')
                                    ->label('To Date')
                                    ->native(false),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('requisition_date', '>=', $date),
                            )
                            ->when(
                                $data['date_to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('requisition_date', '<=', $date),
                            );
                    }),

                Tables\Filters\Filter::make('pending_approval')
                    ->label('Pending Approval')
                    ->query(fn (Builder $query): Builder => $query->where('approval_status', PRFApprovalStatus::PENDING->value))
                    ->default()
                    ->toggle(),

                Tables\Filters\Filter::make('has_payment_instruction')
                    ->label('Has Payment Instruction')
                    ->query(fn (Builder $query): Builder => $query->whereHas('paymentInstruction'))
                    ->toggle(),

                Tables\Filters\Filter::make('my_requisitions')
                    ->label('My Requisitions')
                    ->query(fn (Builder $query): Builder => $query->where('member_id', Auth::user()->member?->id))
                    ->toggle()
                    ->visible(fn () => Auth::user()->member),

                Tables\Filters\Filter::make('assigned_to_me')
                    ->label('Assigned to Me')
                    ->query(fn (Builder $query): Builder => $query->where('appointed_approver_id', Auth::user()->member?->id))
                    ->toggle()
                    ->visible(fn () => Auth::user()->member),

                Tables\Filters\QueryBuilder::make()
                    ->constraints([
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('ulid')
                            ->label('Requisition ID'),
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('remarks')
                            ->label('Remarks'),
                        Tables\Filters\QueryBuilder\Constraints\TextConstraint::make('approval_notes')
                            ->label('Approval Notes'),
                        Tables\Filters\QueryBuilder\Constraints\NumberConstraint::make('total_amount')
                            ->label('Total Amount (cents)'),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('requisition_date')
                            ->label('Requisition Date'),
                        Tables\Filters\QueryBuilder\Constraints\DateConstraint::make('approved_at')
                            ->label('Approved Date'),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('member')
                            ->label('Member')
                            ->multiple(),
                        Tables\Filters\QueryBuilder\Constraints\RelationshipConstraint::make('accountingEvent')
                            ->label('Accounting Event')
                            ->multiple(),
                    ]),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->modalHeading(fn (Requisition $record) => "Requisition: {$record->ulid}")
                        ->modalDescription(fn (Requisition $record) => 'Amount: KES '.number_format($record->total_amount, 2))
                        ->visible(fn () => userCan('view requisition')),

                    Tables\Actions\EditAction::make()
                        ->color('warning')
                        ->successNotificationTitle('Requisition updated successfully')
                        ->visible(fn (Requisition $record) => userCan('edit requisition') &&
                            $record->approval_status === PRFApprovalStatus::PENDING->value
                        ),

                    Tables\Actions\Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Requisition')
                        ->modalDescription(fn (Requisition $record) => "Are you sure you want to approve requisition {$record->ulid} for KES ".
                            number_format($record->total_amount, 2).'?'
                        )
                        ->form([
                            Forms\Components\Textarea::make('approval_notes')
                                ->label('Approval Notes')
                                ->placeholder('Enter notes for approval...')
                                ->rows(3),
                        ])
                        ->action(function (array $data, Requisition $record): void {
                            $record->update([
                                'approval_status' => PRFApprovalStatus::APPROVED->value,
                                'approved_by' => Auth::user()->member?->id,
                                'approved_at' => now(),
                                'approval_notes' => $data['approval_notes'] ?? null,
                            ]);
                        })
                        ->successNotificationTitle('Requisition approved successfully')
                        ->visible(fn (Requisition $record) => userCan('approve requisition') &&
                            $record->approval_status === PRFApprovalStatus::PENDING->value &&
                            ($record->appointed_approver_id === Auth::user()->member?->id || userCan('approve any requisition'))
                        ),

                    Tables\Actions\Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Requisition')
                        ->modalDescription(fn (Requisition $record) => "Are you sure you want to reject requisition {$record->ulid}?"
                        )
                        ->form([
                            Forms\Components\Textarea::make('approval_notes')
                                ->label('Rejection Reason')
                                ->placeholder('Please provide a reason for rejection...')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (array $data, Requisition $record): void {
                            $record->update([
                                'approval_status' => PRFApprovalStatus::REJECTED->value,
                                'approved_by' => Auth::user()->member?->id,
                                'rejected_at' => now(),
                                'approval_notes' => $data['approval_notes'],
                            ]);
                        })
                        ->successNotificationTitle('Requisition rejected')
                        ->visible(fn (Requisition $record) => userCan('approve requisition') &&
                            $record->approval_status === PRFApprovalStatus::PENDING->value &&
                            ($record->appointed_approver_id === Auth::user()->member?->id || userCan('approve any requisition'))
                        ),

                    Tables\Actions\Action::make('request_review')
                        ->label('Request Review')
                        ->icon('heroicon-m-eye')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Request Review')
                        ->modalDescription('This will notify the appointed approver to review this requisition.')
                        ->action(function (Requisition $record): void {
                            $record->update([
                                'approval_status' => PRFApprovalStatus::UNDER_REVIEW->value,
                                'review_requested_at' => now(),
                            ]);
                        })
                        ->successNotificationTitle('Review requested')
                        ->visible(fn (Requisition $record) => userCan('request review requisition') &&
                            $record->approval_status === PRFApprovalStatus::PENDING->value &&
                            $record->appointed_approver_id
                        ),

                    Tables\Actions\Action::make('recall')
                        ->label('Recall')
                        ->icon('heroicon-m-arrow-uturn-left')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Recall Requisition')
                        ->modalDescription(fn (Requisition $record) => "Are you sure you want to recall requisition {$record->ulid}? All approvers and desk members will be notified not to take any action on this requisition."
                        )
                        ->action(function (Requisition $record): void {
                            $record->update([
                                'approval_status' => PRFApprovalStatus::RECALLED->value,
                            ]);
                        })
                        ->successNotificationTitle('Requisition recalled successfully')
                    // ->visible(fn (Requisition $record) => userCan('recall requisition') &&
                    //     in_array($record->approval_status, [
                    //         PRFApprovalStatus::PENDING->value,
                    //         PRFApprovalStatus::UNDER_REVIEW->value,
                    //         PRFApprovalStatus::APPROVED->value,
                    //     ])
                    // )
                    ,
                    Tables\Actions\DeleteAction::make()
                        ->successNotificationTitle('Requisition deleted successfully')
                        ->visible(fn (Requisition $record) => userCan('delete requisition') &&
                            $record->approval_status === PRFApprovalStatus::PENDING->value
                        ),

                    Tables\Actions\ForceDeleteAction::make()
                        ->visible(fn () => userCan('force delete requisition')),

                    Tables\Actions\RestoreAction::make()
                        ->successNotificationTitle('Requisition restored successfully')
                        ->visible(fn () => userCan('restore requisition')),
                ])->label('Actions')
                    ->color('primary')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->button()
                    ->tooltip('Requisition Actions'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotificationTitle('Requisitions deleted successfully')
                        ->visible(fn () => userCan('delete requisition')),

                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('force delete requisition')),

                    Tables\Actions\RestoreBulkAction::make()
                        ->successNotificationTitle('Requisitions restored successfully')
                        ->visible(fn () => userCan('restore requisition')),

                    Tables\Actions\BulkAction::make('bulkApprove')
                        ->label('Bulk Approve')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Approve Requisitions')
                        ->modalDescription('Are you sure you want to approve all selected requisitions?')
                        ->form([
                            Forms\Components\Textarea::make('approval_notes')
                                ->label('Approval Notes')
                                ->placeholder('Enter notes for bulk approval...')
                                ->rows(3),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->approval_status === PRFApprovalStatus::PENDING->value) {
                                    $record->update([
                                        'approval_status' => PRFApprovalStatus::APPROVED->value,
                                        'approved_by' => Auth::user()->member?->id,
                                        'approved_at' => now(),
                                        'approval_notes' => $data['approval_notes'] ?? null,
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->success()
                                ->title('Bulk approval completed')
                                ->body("Approved {$count} requisitions successfully")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('approve requisition')),

                    Tables\Actions\BulkAction::make('assignApprover')
                        ->label('Assign Approver')
                        ->icon('heroicon-m-user-plus')
                        ->color('info')
                        ->form([
                            Forms\Components\Select::make('appointed_approver_id')
                                ->label('Appointed Approver')
                                ->relationship('appointedApprover', 'full_name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->helperText('Select member to approve these requisitions'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $count = $records->count();
                            foreach ($records as $record) {
                                $record->update([
                                    'appointed_approver_id' => $data['appointed_approver_id'],
                                ]);
                            }

                            Notification::make()
                                ->success()
                                ->title('Approver assigned')
                                ->body("Assigned approver to {$count} requisitions")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('assign approver requisition')),

                    Tables\Actions\BulkAction::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-m-arrow-down-tray')
                        ->color('gray')
                        ->action(function (Collection $records): void {
                            // This would typically generate a file download
                            Notification::make()
                                ->success()
                                ->title('Export prepared')
                                ->body('Export for '.$records->count().' requisitions is ready')
                                ->send();
                        })
                        ->visible(fn () => userCan('export requisition')),
                ])->visible(fn () => userCan('delete requisition') || userCan('approve requisition')),
            ])
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RequisitionItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequisitions::route('/'),
            'create' => Pages\CreateRequisition::route('/create'),
            'view' => Pages\ViewRequisition::route('/{record}'),
            'edit' => Pages\EditRequisition::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['member', 'accountingEvent', 'appointedApprover', 'approvedBy', 'paymentInstruction'])
            ->withCount(['requisitionItems'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getDefaultEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['member', 'accountingEvent', 'appointedApprover'])
            ->withCount(['requisitionItems']);
    }

    public static function canAccess(): bool
    {
        return userCan('viewAny requisition');
    }
}
