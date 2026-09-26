<?php

namespace App\Filament\Resources\Letters\Pages;

use App\Filament\Resources\Letters\LetterResource;
use App\Models\Letter;
use Filament\Resources\Pages\CreateRecord;

class CreateLetter extends CreateRecord
{
    protected static string $resource = LetterResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Letter::permission('create'));
    }
}
