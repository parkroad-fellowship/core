<?php

namespace App\Jobs\LessonModule;

use App\Models\LessonModule;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * Takes a lesson out of a module. The lesson stays in the library.
 */
class DeleteJob
{
    use Dispatchable;

    public function __construct(
        public LessonModule $lessonModule,
        public User $actor,
    ) {}

    public function handle(): void
    {
        DB::transaction(function (): void {
            $module = $this->lessonModule->module;
            $this->lessonModule->delete();

            ReorderJob::dispatchSync(
                $module,
                array_values(
                    LessonModule::query()
                        ->where('module_id', $module->id)
                        ->orderBy('order')
                        ->get()
                        ->map(fn(LessonModule $link): string => $link->ulid)
                        ->all(),
                ),
            );
        });
    }
}
