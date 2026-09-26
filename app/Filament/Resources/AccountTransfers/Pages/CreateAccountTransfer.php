<?php

namespace App\Filament\Resources\AccountTransfers\Pages;

use App\Filament\Resources\AccountTransfers\AccountTransferResource;
use App\Jobs\AccountTransfer\CreateJob;
use App\Models\AccountTransfer;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateAccountTransfer extends CreateRecord
{
    protected static string $resource = AccountTransferResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return CreateJob::dispatchSync([...$data, 'recorded_by' => Auth::id()]);
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(AccountTransfer::permission('create'));
    }
}
