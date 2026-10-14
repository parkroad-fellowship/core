<?php

namespace App\Jobs\Middleware;

use App\Exceptions\IntegrationNotConfiguredException;
use Closure;
use Illuminate\Support\Facades\Log;

/**
 * Fail closed without retrying: when the tenant has not configured an integration the job
 * needs, log it once and drop the job instead of burning retries.
 */
class SkipWhenIntegrationMissing
{
    public function handle(object $job, Closure $next): mixed
    {
        try {
            return $next($job);
        } catch (IntegrationNotConfiguredException $exception) {
            Log::warning('Job skipped: integration not configured', [
                'job' => $job::class,
                ...$exception->context(),
            ]);

            if (method_exists($job, 'delete')) {
                $job->delete();
            }

            return null;
        }
    }
}
