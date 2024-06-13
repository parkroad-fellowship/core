<?php

namespace App\Filament\Resources\SchoolTermResource\Pages;

use App\Filament\Resources\SchoolTermResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSchoolTerm extends EditRecord
{
    protected static string $resource = SchoolTermResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->visible(fn () => auth()->user()->can('create school_term')),
            Actions\DeleteAction::make()->visible(fn () => auth()->user()->can('delete school_term')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->can('viewAny school_term');
    }
}
