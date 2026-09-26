<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Enums\PRFActiveStatus;
use App\Enums\PRFLessonType;
use App\Filament\Resources\Lessons\LessonResource;
use App\Jobs\Lesson\UpdateJob;
use App\Models\Lesson;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    /**
     * The form's option fields hold plain values, not enum cases.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [
            ...$data,
            'type' => $data['type'] instanceof PRFLessonType ? $data['type']->value : $data['type'],
            'is_active' => $data['is_active'] instanceof PRFActiveStatus
                ? $data['is_active']->value
                : $data['is_active'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof Lesson);

        $updated = UpdateJob::dispatchSync($data, $record->ulid);
        assert($updated instanceof Lesson);

        return $updated;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->visible(fn() => userCan(Lesson::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Lesson::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Lesson::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Lesson::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Lesson::permission('edit'));
    }
}
