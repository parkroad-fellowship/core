<?php

namespace App\Filament\Resources\MissionGroundSuggestions\Pages;

use App\Filament\Resources\MissionGroundSuggestions\MissionGroundSuggestionResource;
use App\Jobs\MissionGroundSuggestion\CreateJob;
use App\Models\Member;
use App\Models\MissionGroundSuggestion;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMissionGroundSuggestion extends CreateRecord
{
    protected static string $resource = MissionGroundSuggestionResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(MissionGroundSuggestion::permission('create'));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $suggestion = CreateJob::dispatchSync([
            ...$data,
            'suggestor_ulid' => Member::query()
                ->whereKey($data['suggestor_id'] ?? null)
                ->firstOrFail()
                ->ulid,
        ]);
        assert($suggestion instanceof MissionGroundSuggestion);

        return $suggestion;
    }
}
