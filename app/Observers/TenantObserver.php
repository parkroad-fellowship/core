<?php

namespace App\Observers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantObserver
{
    public function creating(Tenant $tenant): void
    {
        if (empty($tenant->slug)) {
            $baseSlug = Str::slug($tenant->name) ?: 'tenant';
            $slug = $baseSlug;
            $counter = 1;

            while (Tenant::query()->where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $tenant->slug = $slug;
        }
    }

    public function created(Tenant $tenant): void
    {
        $centralDomains = config('tenancy.identification.central_domains', []);
        $centralDomain = $centralDomains[0] ?? null;

        if (! $centralDomain) {
            Log::warning('Skipping default tenant domain creation: no central domain configured.', [
                'tenant_id' => $tenant->id,
            ]);

            return;
        }

        $tenant->domains()->firstOrCreate([
            'domain' => "{$tenant->slug}.{$centralDomain}",
        ]);
    }
}
