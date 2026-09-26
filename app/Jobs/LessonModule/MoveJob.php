<?php

namespace App\Jobs\LessonModule;

use App\Models\LessonModule;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * Moves a lesson from one module to another, at the given position (0 = first).
 */
class MoveJob
{
    use Dispatchable;

    public function __construct(
        public LessonModule $lessonModule,
        public Module $to,
        public User $actor,
        public ?int $position = null,
    ) {}

    public function handle(): LessonModule
    {
        return DB::transaction(function (): LessonModule {
            $lesson = $this->lessonModule->lesson;

            $moved = CreateJob::dispatchSync(['module_ulid' => $this->to->ulid, 'lesson_ulid' => $lesson->ulid]);
            assert($moved instanceof LessonModule);

            DeleteJob::dispatchSync($this->lessonModule, $this->actor);

            if ($this->position !== null) {
                $ulids = LessonModule::query()
                    ->where('module_id', $this->to->id)
                    ->whereKeyNot($moved->id)
                    ->orderBy('order')
                    ->get()
                    ->map(fn(LessonModule $link): string => $link->ulid)
                    ->all();

                array_splice($ulids, max(0, $this->position), 0, [$moved->ulid]);

                ReorderJob::dispatchSync($this->to, $ulids);
            }

            return $moved->refresh();
        });
    }
}
