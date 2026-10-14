<?php

namespace App\Exceptions;

use App\Enums\PRFIntegration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown when a tenant uses a feature whose integration it has not configured.
 * API requests receive a 422 with a readable message; jobs log and skip.
 */
class IntegrationNotConfiguredException extends RuntimeException
{
    /**
     * @param  list<string>  $missingSettings
     */
    public function __construct(
        public readonly PRFIntegration $integration,
        public readonly array $missingSettings = [],
    ) {
        parent::__construct("{$integration->getLabel()} is not configured for this organisation.");
    }

    public function render(Request $request): ?JsonResponse
    {
        if (!$request->expectsJson()) {
            return null;
        }

        return response()->json([
            'message' => $this->getMessage(),
            'integration' => $this->integration->name,
        ], 422);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return [
            'tenant' => tenant('id'),
            'integration' => $this->integration->name,
            'missing' => $this->missingSettings,
        ];
    }
}
