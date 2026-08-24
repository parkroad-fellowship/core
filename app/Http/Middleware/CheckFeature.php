<?php

namespace App\Http\Middleware;

use App\Enums\PRFFeature;
use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CheckFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        try {
            $featureEnum = PRFFeature::from($feature);
        } catch (\ValueError) {
            return response()->json([
                'message' => 'Unknown feature flag.',
                'code' => 'UNKNOWN_FEATURE',
            ], 422);
        }

        if (!AppSetting::isFeatureEnabled($featureEnum)) {
            return response()->json([
                'message' => 'This feature is not enabled for your fellowship.',
                'code' => 'FEATURE_DISABLED',
            ], 403);
        }

        return $next($request);
    }
}
