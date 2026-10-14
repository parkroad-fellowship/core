<?php

namespace App\Filament\Resources\FinancialAccounts\Pages;

use App\Filament\Resources\FinancialAccounts\FinancialAccountResource;
use App\Jobs\FinancialAccount\CreateJob;
use App\Models\FinancialAccount;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFinancialAccount extends CreateRecord
{
    protected static string $resource = FinancialAccountResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return CreateJob::dispatchSync($data);
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(FinancialAccount::permission('create'));
    }
}
