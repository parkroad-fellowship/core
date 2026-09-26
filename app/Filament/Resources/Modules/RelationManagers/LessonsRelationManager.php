<?php

namespace App\Filament\Resources\Modules\RelationManagers;

use App\Filament\Concerns\BuildsCurriculum;
use App\Models\Module;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * The module's lessons in order: drag to reorder, add from the library or write a new one.
 */
class LessonsRelationManager extends RelationManager
{
    use BuildsCurriculum;

    protected static string $relationship = 'lessonModules';

    protected static ?string $title = 'Lessons';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-queue-list';

    protected string $view = 'filament.e-learning.module-lessons';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord instanceof Module ? (string) $ownerRecord->lessonModules()->count() : null;
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function module(): Module
    {
        $module = $this->getOwnerRecord();

        assert($module instanceof Module);

        return $module->load([
            'lessonModules' => fn(Relation $query) => $query->orderBy('order'),
            'lessonModules.lesson' => fn(Relation $query) => $query->withCount('lessonModules')->with('media'),
        ]);
    }
}
