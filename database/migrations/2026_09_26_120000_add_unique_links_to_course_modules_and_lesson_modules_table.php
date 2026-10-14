<?php

use App\Models\CourseModule;
use App\Models\LessonModule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A module appears once per course and a lesson once per module. Removing a link soft-deletes it
 * and adding it back restores that row, so the unique index covers trashed rows too.
 *
 * Existing duplicates are cleaned up first: one row per pair is kept (a live one if there is one),
 * and each course's modules and each module's lessons are renumbered 1..n.
 */
return new class extends Migration {
    /**
     * @var array<string, array{model: class-string<Model>, parent: string, child: string}>
     */
    private const LINKS = [
        'course_modules' => ['model' => CourseModule::class, 'parent' => 'course_id', 'child' => 'module_id'],
        'lesson_modules' => ['model' => LessonModule::class, 'parent' => 'module_id', 'child' => 'lesson_id'],
    ];

    public function up(): void
    {
        foreach (self::LINKS as $table => ['model' => $model, 'parent' => $parent, 'child' => $child]) {
            $this->removeDuplicates($model, $parent, $child);
            $this->renumber($model, $parent);

            $index = "{$table}_tenant_id_{$parent}_{$child}_unique";

            if (!Schema::hasIndex($table, $index)) {
                Schema::table($table, fn(Blueprint $blueprint) => $blueprint->unique(
                    ['tenant_id', $parent, $child],
                    $index,
                ));
            }
        }
    }

    public function down(): void
    {
        foreach (self::LINKS as $table => ['parent' => $parent, 'child' => $child]) {
            Schema::table($table, fn(Blueprint $blueprint) => $blueprint->dropUnique(
                "{$table}_tenant_id_{$parent}_{$child}_unique",
            ));
        }
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function removeDuplicates(string $model, string $parent, string $child): void
    {
        $model::query()
            ->withoutGlobalScopes()
            ->get()
            ->groupBy(fn(Model $link): string => serialize([
                $link->getAttribute('tenant_id'),
                $link->getAttribute($parent),
                $link->getAttribute($child),
            ]))
            ->each(function ($links): void {
                $keep = $links->sortBy([
                    fn(Model $a, Model $b): int => (
                        (int) ($a->getAttribute('deleted_at') !== null)
                        <=> (int) ($b->getAttribute('deleted_at') !== null)
                    ),
                    fn(Model $a, Model $b): int => $a->getKey() <=> $b->getKey(),
                ])->first();

                $links
                    ->reject(fn(Model $link): bool => $link->is($keep))
                    ->each(fn(Model $link) => $link->forceDelete());
            });
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function renumber(string $model, string $parent): void
    {
        $model::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->orderBy('order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn(Model $link): string => serialize([
                $link->getAttribute('tenant_id'),
                $link->getAttribute($parent),
            ]))
            ->each(fn($links) => $links
                ->values()
                ->each(fn(Model $link, int $position) => $link->forceFill(['order' => $position + 1])->saveQuietly()));
    }
};
