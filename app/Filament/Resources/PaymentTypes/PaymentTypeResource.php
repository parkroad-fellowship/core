<?php

namespace App\Filament\Resources\PaymentTypes;

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\ContentSchema;
use App\Filament\Forms\Schemas\StatusSchema;
use App\Filament\Resources\PaymentTypes\Pages\CreatePaymentType;
use App\Filament\Resources\PaymentTypes\Pages\EditPaymentType;
use App\Filament\Resources\PaymentTypes\Pages\ListPaymentTypes;
use App\Filament\Resources\PaymentTypes\Pages\ViewPaymentType;
use App\Models\PaymentType;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PaymentTypeResource extends Resource
{
    protected static ?string $model = PaymentType::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Treasurer';

    protected static ?string $modelLabel = 'Payment Type';

    protected static ?string $pluralModelLabel = 'Payment Types';

    protected static ?string $navigationTooltip = 'Manage different types of payments';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Payment Type Details')
                    ->description('Define the type of payment that members can make')
                    ->icon('heroicon-o-credit-card')
                    ->schema([
                        Grid::make(2)
                            ->columnSpanFull()
                            ->schema([
                                ContentSchema::nameField(
                                    name: 'name',
                                    label: 'Payment Type Name',
                                    placeholder: 'e.g., Membership Fee, Tithe, Offering',
                                    helperText: 'Enter a clear name that describes this payment type',
                                )
                                    ->prefixIcon('heroicon-o-credit-card'),

                                StatusSchema::enumSelect(
                                    name: 'is_active',
                                    label: 'Status',
                                    enumClass: PRFActiveStatus::class,
                                    default: PRFActiveStatus::ACTIVE->value,
                                    helperText: 'Active payment types are available for recording; inactive ones are hidden',
                                ),
                            ]),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Section::make('Additional Information')
                    ->description('Provide more details about this payment type')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        ContentSchema::descriptionField(
                            name: 'description',
                            label: 'Description',
                            required: true,
                            placeholder: 'e.g., Monthly membership contribution for all registered members...',
                            helperText: 'Explain what this payment type is for and when it should be used',
                        ),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Payment Type')
                    ->icon('heroicon-o-credit-card')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->tooltip('The name of this payment type'),

                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),

                TextColumn::make('payments_count')
                    ->label('Payments')
                    ->counts('payments')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-banknotes')
                    ->tooltip('Total number of payments recorded with this type'),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => PRFActiveStatus::fromValue($state)->getLabel())
                    ->color(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'success' : 'danger')
                    ->icon(fn ($state) => $state === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->sortable()
                    ->tooltip(fn ($state) => $state === PRFActiveStatus::ACTIVE->value
                        ? 'This payment type is active and available for use'
                        : 'This payment type is inactive and hidden from selection'),

                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('When this payment type was first created'),

                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('When this payment type was last updated'),

                TextColumn::make('deleted_at')
                    ->label('Date Deleted')
                    ->dateTime('M j, Y g:i A')
                    ->timezone(Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip('When this payment type was removed'),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Show Deleted')
                    ->placeholder('Active payment types only'),

                SelectFilter::make('is_active')
                    ->label('Filter by Status')
                    ->options([
                        PRFActiveStatus::ACTIVE->value => 'Active',
                        PRFActiveStatus::INACTIVE->value => 'Inactive',
                    ])
                    ->default(PRFActiveStatus::ACTIVE->value)
                    ->placeholder('All statuses'),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('info')
                        ->visible(fn () => userCan('view payment type'))
                        ->tooltip('View full payment type details'),

                    EditAction::make()
                        ->color('warning')
                        ->visible(fn () => userCan('edit payment type'))
                        ->tooltip('Make changes to this payment type')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Payment type updated')
                                ->body('The payment type has been saved successfully.')
                        ),

                    Action::make('toggle_status')
                        ->label(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                        ->color(fn ($record) => $record->is_active === PRFActiveStatus::ACTIVE->value ? 'danger' : 'success')
                        ->action(function ($record) {
                            $newStatus = $record->is_active === PRFActiveStatus::ACTIVE->value
                                ? PRFActiveStatus::INACTIVE->value
                                : PRFActiveStatus::ACTIVE->value;
                            $record->update(['is_active' => $newStatus]);

                            $statusLabel = $newStatus === PRFActiveStatus::ACTIVE->value ? 'activated' : 'deactivated';
                            Notification::make()
                                ->success()
                                ->title('Status updated')
                                ->body("The payment type has been {$statusLabel} successfully.")
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Change Payment Type Status')
                        ->modalDescription('Are you sure you want to change the status of this payment type?')
                        ->visible(fn () => userCan('edit payment type'))
                        ->tooltip('Change payment type status'),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => userCan('delete payment type')),

                    ForceDeleteBulkAction::make()
                        ->visible(fn () => userCan('delete payment type')),

                    RestoreBulkAction::make()
                        ->visible(fn () => userCan('delete payment type')),

                    BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::ACTIVE->value]));

                            Notification::make()
                                ->success()
                                ->title('Payment types activated')
                                ->body("{$count} payment types have been activated successfully.")
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit payment type')),

                    BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-eye-slash')
                        ->color('danger')
                        ->action(function (Collection $records) {
                            $count = $records->count();
                            $records->each(fn ($record) => $record->update(['is_active' => PRFActiveStatus::INACTIVE->value]));

                            Notification::make()
                                ->success()
                                ->title('Payment types deactivated')
                                ->body("{$count} payment types have been deactivated successfully.")
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => userCan('edit payment type')),
                ])->visible(fn () => userCan('delete payment type')),
            ])
            ->defaultSort('name', 'asc')
            ->striped()
            ->searchPlaceholder('Search payment types...')
            ->emptyStateHeading('No payment types found')
            ->emptyStateDescription('Create your first payment type to start recording member payments.')
            ->emptyStateIcon('heroicon-o-credit-card');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentTypes::route('/'),
            'create' => CreatePaymentType::route('/create'),
            'view' => ViewPaymentType::route('/{record}'),
            'edit' => EditPaymentType::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canAccess(): bool
    {
        return userCan('viewAny payment type');
    }
}
