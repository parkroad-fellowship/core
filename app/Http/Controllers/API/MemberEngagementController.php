<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberEngagement\GetEngagementRequest;
use App\Http\Resources\MemberEngagement\Resource;
use App\Jobs\MemberEngagement\GetEngagementJob;
use App\Models\Member;

/**
 * Handles API requests for Member Engagement Statistics.
 *
 * Provides comprehensive statistics about a member's engagement including
 * missions, courses, prayers, events, and overall impact.
 */
class MemberEngagementController extends Controller
{
    /**
     * Get engagement statistics for a specific member.
     */
    public function show(GetEngagementRequest $request, string $memberUlid): Resource
    {
        $validated = $request->validated();

        $member = Member::query()
            ->where('ulid', $memberUlid)
            ->firstOrFail();

        $engagementData = GetEngagementJob::dispatchSync($member, $validated);

        return new Resource($engagementData);
    }
}
