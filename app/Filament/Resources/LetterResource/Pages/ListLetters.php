<?php

namespace App\Filament\Resources\LetterResource\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\LetterResource;
use Filament\Actions;
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
