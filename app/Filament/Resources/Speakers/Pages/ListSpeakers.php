<?php

namespace App\Filament\Resources\Speakers\Pages;

use App\Filament\Exports\SpeakerExporter;
use App\Filament\Resources\Speakers\SpeakerResource;
use App\Models\Speaker;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Resources\Pages\ListRecords;

class ListSpeakers extends ListRecords
{
    protected static string $resource = SpeakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(SpeakerExporter::class)
                ->label('Export Speakers')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(userCan(Speaker::permission('viewAny'))),
            CreateAction::make()->visible(userCan(Speaker::permission('create'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Speaker::permission('viewAny'));
    }
}
