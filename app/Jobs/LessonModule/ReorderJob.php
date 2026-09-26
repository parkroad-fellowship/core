<?php

namespace App\Jobs\LessonModule;

use App\Events\Course\CourseCurriculumChanged;
use App\Models\LessonModule;
use App\Models\Module;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Numbers a module's lessons 1..n in the given order.
 */
class ReorderJob
{
    use Dispatchable;

    /**
     * @param  list<string>  $ulids  the module's lesson links, first to last
     */
    public function __construct(
        public Module $module,
        public array $ulids,
    ) {}

    public function handle(): void
    {
        $positions = array_flip($this->ulids);

        LessonModule::query()
            ->where('module_id', $this->module->id)
            ->whereIn('ulid', $this->ulids)
            ->get()
            ->each(function (LessonModule $link) use ($positions): void {
                $order = $positions[$link->ulid] + 1;

                if ($link->order !== $order) {
                    $link->update(['order' => $order]);
                }
            });

        CourseCurriculumChanged::forModule($this->module);
    }
}
