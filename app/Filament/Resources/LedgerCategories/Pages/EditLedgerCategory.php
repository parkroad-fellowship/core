<?php

namespace App\Filament\Resources\LedgerCategories\Pages;

use App\Filament\Resources\LedgerCategories\LedgerCategoryResource;
use App\Jobs\LedgerCategory\UpdateJob;
use App\Models\LedgerCategory;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLedgerCategory extends EditRecord
{
    protected static string $resource = LedgerCategoryResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof LedgerCategory);

        return UpdateJob::dispatchSync($data, $record->ulid);
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->visible(fn(): bool => userCan(LedgerCategory::permission('delete'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(LedgerCategory::permission('edit'));
    }
}
