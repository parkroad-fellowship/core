<?php

namespace App\Filament\Resources\Professions\Pages;

use App\Filament\Resources\Professions\ProfessionResource;
use App\Models\Profession;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProfession extends ViewRecord
{
    protected static string $resource = ProfessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->visible(fn() => userCan(Profession::permission('edit'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Profession::permission('view'));
    }
}
