<?php

namespace App\Filament\Resources\AccountTransfers\Pages;

use App\Enums\PRFFinancialAccountType;
use App\Filament\Resources\AccountTransfers\AccountTransferResource;
use App\Jobs\AccountTransfer\CreateJob;
use App\Models\AccountTransfer;
use App\Models\FinancialAccount;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ListAccountTransfers extends ListRecords
{
    protected static string $resource = AccountTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('record_paystack_settlement')
                ->label('Record Paystack settlement')
                ->icon('heroicon-o-credit-card')
                ->color('info')
                ->schema(AccountTransferResource::transferSchemaFields())
                ->fillForm(fn(): array => [
                    'from_financial_account_ulid' => $this->accountUlid(PRFFinancialAccountType::PAYSTACK),
                    'to_financial_account_ulid' => $this->accountUlid(PRFFinancialAccountType::BANK),
                    'transferred_on' => now()->toDateString(),
                    'description' => 'Paystack settlement to bank',
                ])
                ->action(function (array $data): void {
                    try {
                        CreateJob::dispatchSync([...$data, 'recorded_by' => Auth::id()]);
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Settlement not recorded')
                            ->body($exception->getMessage())
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title('Settlement recorded')
                        ->body('The Paystack balance moved to the bank; income totals are unchanged.')
                        ->send();
                })
                ->visible(fn(): bool => userCan(AccountTransfer::permission('create')))
                ->tooltip('Move settled online giving from the Paystack account to the bank'),

            CreateAction::make()->visible(fn(): bool => userCan(AccountTransfer::permission('create'))),
        ];
    }

    private function accountUlid(PRFFinancialAccountType $type): ?string
    {
        return FinancialAccount::query()
            ->active()
            ->where('type', $type)
            ->orderBy('id')
            ->first()
            ?->ulid;
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(AccountTransfer::permission('viewAny'));
    }
}
