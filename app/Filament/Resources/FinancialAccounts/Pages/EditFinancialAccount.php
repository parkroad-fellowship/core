<?php

namespace App\Filament\Resources\FinancialAccounts\Pages;

use App\Filament\Resources\FinancialAccounts\FinancialAccountResource;
use App\Jobs\FinancialAccount\UpdateJob;
use App\Models\FinancialAccount;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditFinancialAccount extends EditRecord
{
    protected static string $resource = FinancialAccountResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof FinancialAccount);

        return UpdateJob::dispatchSync($data, $record->ulid);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn(): bool => userCan(FinancialAccount::permission('view'))),
            DeleteAction::make()->visible(fn(): bool => userCan(FinancialAccount::permission('delete'))),
            ForceDeleteAction::make()->visible(fn(): bool => userCan(FinancialAccount::permission('forceDelete'))),
            RestoreAction::make()->visible(fn(): bool => userCan(FinancialAccount::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(FinancialAccount::permission('edit'));
    }
}
