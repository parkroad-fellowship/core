<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use App\Jobs\Lesson\CreateJob;
use App\Models\Lesson;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = CreateJob::dispatchSync($data);
        assert($record instanceof Lesson);

        return $record;
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Lesson::permission('create'));
    }
}
