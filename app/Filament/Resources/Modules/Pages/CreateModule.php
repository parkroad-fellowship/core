<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Filament\Resources\Modules\ModuleResource;
use App\Jobs\Module\CreateJob;
use App\Models\Module;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateModule extends CreateRecord
{
    protected static string $resource = ModuleResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = CreateJob::dispatchSync($data);
        assert($record instanceof Module);

        return $record;
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Module::permission('create'));
    }
}
