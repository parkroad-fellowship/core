<?php

namespace App\Filament\Resources\Courses\Pages;

use App\Enums\PRFActiveStatus;
use App\Filament\Resources\Courses\CourseResource;
use App\Jobs\Course\CreateJob;
use App\Models\Course;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    protected static bool $canCreateAnother = false;

    public function getSubheading(): ?string
    {
        return 'Start with the name and a short summary. Next you’ll add modules and lessons, then publish when it’s ready.';
    }

    /**
     * New courses start hidden so nobody sees a half-built course.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $record = CreateJob::dispatchSync([...$data, 'is_active' => PRFActiveStatus::INACTIVE]);
        assert($record instanceof Course);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return CourseResource::getUrl('edit', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Course created')
            ->body('Now add its modules and lessons below. It stays hidden until you publish it.');
    }

    public static function canAccess(array $parameters = []): bool
    {
        return userCan(Course::permission('create'));
    }
}
