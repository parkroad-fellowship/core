<?php

namespace App\Filament\Resources\Letters\Pages;

use App\Filament\Resources\Letters\LetterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLetters extends ListRecords
{
    protected static string $resource = LetterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => userCan('create letter')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny letter');
    }
}
