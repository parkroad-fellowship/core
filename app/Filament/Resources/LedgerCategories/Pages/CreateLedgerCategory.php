<?php

namespace App\Filament\Resources\LedgerCategories\Pages;

use App\Filament\Resources\LedgerCategories\LedgerCategoryResource;
use App\Jobs\LedgerCategory\CreateJob;
use App\Models\LedgerCategory;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLedgerCategory extends CreateRecord
{
    protected static string $resource = LedgerCategoryResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return CreateJob::dispatchSync($data);
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(LedgerCategory::permission('create'));
    }
}
