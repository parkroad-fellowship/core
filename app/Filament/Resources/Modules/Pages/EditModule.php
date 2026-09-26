<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Enums\PRFActiveStatus;
use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Modules\ModuleResource;
use App\Jobs\Module\UpdateJob;
use App\Models\Module;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditModule extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = ModuleResource::class;

    /**
     * The form's option fields hold plain values, not enum cases.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            ...$data,
            'is_active' => $data['is_active'] instanceof PRFActiveStatus
                ? $data['is_active']->value
                : $data['is_active'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof Module);

        $updated = UpdateJob::dispatchSync($data, $record->ulid);
        assert($updated instanceof Module);

        return $updated;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(Module::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Module::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Module::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Module::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Module::permission('edit'));
    }
}
