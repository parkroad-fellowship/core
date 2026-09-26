<?php

namespace App\Filament\Resources\Professions\Pages;

use App\Filament\Resources\Professions\ProfessionResource;
use App\Models\Profession;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProfessions extends ListRecords
{
    protected static string $resource = ProfessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn() => userCan(Profession::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Profession::permission('viewAny'));
    }
}
