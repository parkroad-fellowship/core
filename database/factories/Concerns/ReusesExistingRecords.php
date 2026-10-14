<?php

namespace Database\Factories\Concerns;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

trait ReusesExistingRecords
{
    /**
     * Reuse a random existing record (keeps seeded data realistic) or fall back to a
     * fresh factory, so the factory also works against an empty test database.
     *
     * @param  class-string<Model>  $model
     * @return Model|Factory<Model>
     */
    protected function existingOrNew(string $model): Model|Factory
    {
        return $model::query()->inRandomOrder()->first() ?? $model::factory();
    }
}
