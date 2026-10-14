<?php

namespace App\Filament\Resources\AccountTransfers\Pages;

use App\Filament\Resources\AccountTransfers\AccountTransferResource;
use App\Jobs\AccountTransfer\UpdateJob;
use App\Models\AccountTransfer;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAccountTransfer extends EditRecord
{
    protected static string $resource = AccountTransferResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        assert($record instanceof AccountTransfer);

        $data['from_financial_account_ulid'] = $record->fromAccount?->ulid;
        $data['to_financial_account_ulid'] = $record->toAccount?->ulid;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof AccountTransfer);

        return UpdateJob::dispatchSync($data, $record->ulid);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn(): bool => userCan(AccountTransfer::permission('view'))),
            DeleteAction::make()->visible(fn(): bool => userCan(AccountTransfer::permission('delete'))),
            ForceDeleteAction::make()->visible(fn(): bool => userCan(AccountTransfer::permission('forceDelete'))),
            RestoreAction::make()->visible(fn(): bool => userCan(AccountTransfer::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(AccountTransfer::permission('edit'));
    }
}
