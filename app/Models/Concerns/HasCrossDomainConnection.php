<?php

namespace App\Models\Concerns;

trait HasCrossDomainConnection
{
    /**
     * Get the connection name for the model. This model is cross-domain.
     */
    public function getConnectionName(): ?string
    {
        return config('tenancy.database.central_connection', config('database.default'));
    }
}
