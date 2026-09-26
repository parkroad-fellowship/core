<?php

namespace App\Filament\Resources\Pledges\RelationManagers;

use App\Enums\PRFPledgeInstallmentMethod;
use App\Jobs\Pledge\RecordInstallmentJob;
use App\Models\FinancialAccount;
use App\Models\PledgeInstallment;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

/**
 * Lets the Treasurer record follow-through installments straight on a pledge.
 * The PledgeInstallmentObserver advances the pledge's due date automatically.
 */
class PledgeInstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    protected static ?string $recordTitleAttribute = 'fulfilled_on';

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $title = 'Follow-through';

    protected static ?string $modelLabel = 'Installment';

    protected static ?string $pluralModelLabel = 'Installments';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Record Follow-through')
                ->columnSpanFull()
                ->description('Record an installment received against this pledge')
                ->icon('heroicon-o-check-circle')
                ->schema([
                    TextInput::make('amount')
                        ->label('Amount received')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->prefix('KES')
                        ->columnSpanFull(),
                    DatePicker::make('fulfilled_on')
                        ->label('Date received')
                        ->native(false)
                        ->default(today())
                        ->maxDate(today())
                        ->columnSpanFull(),
                    Select::make('method')
                        ->label('Method')
                        ->options(
                            fn(): array => collect(PRFPledgeInstallmentMethod::offline())
                                ->mapWithKeys(fn(PRFPledgeInstallmentMethod $method): array => [
                                    $method->value => $method->getLabel(),
                                ])->all(),
                        )
                        ->placeholder('Derive from the account')
                        ->helperText('Leave blank to derive from the receiving account')
                        ->columnSpanFull(),
                    Select::make('financial_account_ulid')
                        ->label('Received in')
                        ->options(
                            fn(): array => FinancialAccount::query()
                                ->active()
                                ->orderBy('name')
                                ->pluck('name', 'ulid')
                                ->all(),
                        )
                        ->searchable()
                        ->preload()
                        ->placeholder('No receipt')
                        ->helperText('Leave blank to record without receipting into the cashbook')
                        ->columnSpanFull(),
                    TextInput::make('reference')
                        ->label('Reference')
                        ->maxLength(255)
                        ->placeholder('M-Pesa code or bank slip')
                        ->columnSpanFull(),
                    Toggle::make('send_receipt')->label('Send receipt')->default(true)->columnSpanFull(),
                    Textarea::make('notes')->label('Notes')->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('amount')->label('Amount')->sortable(),
            TextColumn::make('fulfilled_on')->label('Date')->sortable(),
            TextColumn::make('method')->label('Method')->badge(),
            TextColumn::make('ledgerEntry.receipt_number')->label('Receipt')->copyable()->placeholder('—'),
            TextColumn::make('notes')->label('Notes')->limit(60),
            TextColumn::make('created_at')->label('Recorded At')->dateTime('M j, Y g:i A')->sortable(),
        ])->filters([
            TrashedFilter::make()->label('Deleted')->placeholder('All'),
        ])->toolbarActions([
            CreateAction::make()
                ->label('Record installment')
                ->using(fn(array $data): PledgeInstallment => RecordInstallmentJob::dispatchSync([
                    ...$data,
                    'pledge_ulid' => $this->getOwnerRecord()->ulid,
                ], Auth::user()))
                ->visible(fn(): bool => userCan(PledgeInstallment::permission('create'))),
            BulkActionGroup::make([
                DeleteBulkAction::make(),
                ForceDeleteBulkAction::make(),
                RestoreBulkAction::make(),
            ]),
        ])->defaultSort('fulfilled_on', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('ledgerEntry')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
