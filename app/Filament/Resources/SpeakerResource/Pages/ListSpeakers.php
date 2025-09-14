<?php

namespace App\Filament\Resources\SpeakerResource\Pages;

use App\Filament\Exports\SpeakerExporter;
use App\Filament\Resources\SpeakerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSpeakers extends ListRecords
{
    protected static string $resource = SpeakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(SpeakerExporter::class)
                ->label('Export Speakers')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(userCan('viewAny speaker')),
            Actions\CreateAction::make()->visible(userCan('create speaker')),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan('viewAny speaker');
    }
}
