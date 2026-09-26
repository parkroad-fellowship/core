<?php

namespace App\Policies;

use App\Enums\PRFRole;
use App\Models\LedgerCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The app posts to coded categories automatically; they can be renamed but never removed,
 * not even by a super admin.
 */
class LedgerCategoryPolicy extends BasePolicy
{
    protected string $modelClass = LedgerCategory::class;

    public function before(User $user, string $ability): ?bool
    {
        return in_array($ability, ['delete', 'forceDelete'], true) ? null : parent::before($user, $ability);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->isRemovable($model) && ($user->hasRole(PRFRole::SUPER_ADMIN) || parent::delete($user, $model));
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return (
            $this->isRemovable($model)
            && ($user->hasRole(PRFRole::SUPER_ADMIN) || parent::forceDelete($user, $model))
        );
    }

    private function isRemovable(Model $model): bool
    {
        return $model instanceof LedgerCategory && $model->code === null;
    }
}
