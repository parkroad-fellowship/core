<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Filament\Concerns\HasAlpineRelationManagerTabs;
use App\Filament\Resources\Courses\CourseResource;
use App\Jobs\Course\UpdateJob;
use App\Models\Course;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditCourse extends EditRecord
{
    use HasAlpineRelationManagerTabs;

    protected static string $resource = CourseResource::class;

    public function getSubheading(): ?string
    {
        return 'Build the course in the Curriculum tab below. Publish it when every module has its lessons.';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof Course);

        $updated = UpdateJob::dispatchSync($data, $record->ulid);
        assert($updated instanceof Course);

        return $updated;
    }

    protected function getHeaderActions(): array
    {
        return [
            CourseResource::publishAction(),
            CourseResource::hideAction(),
            ViewAction::make()->visible(fn() => userCan(Course::permission('view'))),
            DeleteAction::make()->visible(fn() => userCan(Course::permission('delete'))),
            ForceDeleteAction::make()->visible(fn() => userCan(Course::permission('forceDelete'))),
            RestoreAction::make()->visible(fn() => userCan(Course::permission('restore'))),
        ];
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Course::permission('edit'));
    }
}
