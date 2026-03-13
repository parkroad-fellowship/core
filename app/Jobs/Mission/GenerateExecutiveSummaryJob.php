<?php

namespace App\Jobs\Mission;

use App\Enums\PRFMissionRole;
use App\Enums\PRFMissionStatus;
use App\Enums\PRFMissionSubscriptionStatus;
use App\Enums\PRFSoulDecisionType;
use App\Models\Mission;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GenerateExecutiveSummaryJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 5;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     * Exponential backoff: 30s, 60s, 120s, 240s, 480s
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60, 120, 240, 480];
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(2);
    }

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mission $mission,
    ) {
        //
    }

    /**
     * Handle a job failure after all retries exhausted.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('GenerateExecutiveSummaryJob failed permanently', [
            'mission_id' => $this->mission->id,
            'exception' => $exception?->getMessage(),
        ]);

        // Store a failure message so users know the summary couldn't be generated
        Mission::withoutEvents(function (): void {
            Mission::where('id', $this->mission->id)->update([
                'executive_summary' => 'Executive summary generation failed after multiple attempts. Please try again later or contact support.',
            ]);
        });
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Refresh from database to get latest state and reduce serialized payload size
        $mission = Mission::find($this->mission->id);

        if (! $mission) {
            Log::warning('Mission not found for executive summary generation', ['mission_id' => $this->mission->id]);

            return;
        }

        $mission->load([
            'missionType',
            'school',
            'schoolTerm',
            'missionSubscriptions.member.profession',
            'missionSubscriptions.member.church',
            'debriefNotes',
            'missionQuestions',
            'souls.classGroup',
            'missionSessions.facilitator',
            'missionSessions.speaker',
            'missionSessions.classGroup',
            'accountingEvent',
            'accountingEvent.allocationEntries',
            'accountingEvent.refunds',
            'accountingEvent.latestRefund',
            'school.budgetEstimates',
            'school.budgetEstimates.budgetEstimateEntries',
            'school.budgetEstimates.budgetEstimateEntries.expenseCategory',
            'requisitions',
            'requisitions.requisitionItems',
            'requisitions.requisitionItems.expenseCategory',
            'offlineMembers',
        ]);

        $mission->loadCount(['missionPhotos', 'missionVideos']);

        // Historical missions at this school (excluding current)
        $previousMissions = $mission->school_id
            ? Mission::where('school_id', $mission->school_id)
                ->where('id', '!=', $mission->id)
                ->orderBy('start_date', 'desc')
                ->withCount(['souls', 'missionSubscriptions', 'missionSessions'])
                ->get()
            : collect();

        $historicalSummary = '';
        $previousExecutiveSummary = '';
        if ($previousMissions->isNotEmpty()) {
            $missionCount = $previousMissions->count();
            $totalHistoricalSouls = $previousMissions->sum('souls_count');
            $avgSouls = round($totalHistoricalSouls / $missionCount, 1);
            $avgTeam = round($previousMissions->sum('mission_subscriptions_count') / $missionCount, 1);
            $avgSessions = round($previousMissions->sum('mission_sessions_count') / $missionCount, 1);

            $historicalSummary = "School History: {$missionCount} previous mission(s)\n";
            $historicalSummary .= "Averages — Souls: {$avgSouls}, Team Size: {$avgTeam}, Sessions: {$avgSessions}\n";
            $historicalSummary .= "Total Souls Won at This School (all time): {$totalHistoricalSouls}\n\n";

            $historicalSummary .= "Mission-by-Mission (most recent first):\n";
            $historicalSummary .= $previousMissions->map(function (Mission $prev) {
                $date = $prev->start_date?->format('d M Y') ?? 'Unknown date';
                $theme = $prev->theme ?? 'No theme';
                $statusLabel = $prev->status ? PRFMissionStatus::fromValue($prev->status)->getLabel() : 'Unknown';

                return "- {$date} | \"{$theme}\" | Status: {$statusLabel} | Souls: {$prev->souls_count} | Team: {$prev->mission_subscriptions_count} | Sessions: {$prev->mission_sessions_count}";
            })->implode("\n");

            // Include the most recent mission's executive summary for continuity
            $lastMission = $previousMissions->first();
            if (filled($lastMission->executive_summary)) {
                $previousExecutiveSummary = $lastMission->executive_summary;
            } else {
                $previousExecutiveSummary = 'No executive summary was generated for the most recent previous mission.';
            }
        } else {
            $historicalSummary = 'This is the first mission at this school.';
            $previousExecutiveSummary = 'N/A — first mission at this school.';
        }

        /**
         * Updated System Prompt based on PRF Constitution 2017
         * and Gemini Prompting Guidelines.
         */
        $systemPrompt = <<<'EOT'
            **PERSONA:**
            You are the Senior Mission Strategist and Executive Liaison for Parkroad Fellowship (PRF). You are an expert in Christian ministry administration and impact evaluation, with a deep understanding of PRF’s constitutional mandate.

            **CONTEXT & CONSTITUTIONAL ALIGNMENT:**
            PRF is an interdenominational lay ministry called to preach the Gospel to youth in schools and colleges. According to our Constitution, we use our "marketplace acquired skills" to instruct the youth on "holistic living, values, education, and career choices." Every report must reflect our mission of making disciples of Christ from succeeding generations.

            **TASK:**
            Create an elaborate, comprehensive Mission Impact Report for all stakeholders (Leadership, Members, and School Administrations). This report must analyze the data provided to show how we are fulfilling our constitutional objects.

            **STRUCTURE & REQUIREMENTS:**

            1. **EXECUTIVE SUMMARY & CONSTITUTIONAL PURPOSE**
               - High-level overview of the mission's success.
               - Explicit mention of how this mission advanced the goal of "proclaiming the Gospel in schools/colleges."

            2. **HOLISTIC MINISTRY & MARKETPLACE SKILLS**
               - Elaborate on how the sessions addressed "wholesome living, values, and career choices" (marketplace skills).
               - Analyze how the "lay ministry" aspect (professional diversity of the team) impacted the students.

            3. **TEAM DYNAMICS & FELLOWSHIP**
               - Analyze team composition and the effectiveness of the "interdenominational" team.
               - Reflect on member participation as a tool for "team development and fellowship."

            4. **SPIRITUAL IMPACT & DISCIPLESHIP DEPTH**
               - Detailed breakdown of souls won and decision types.
               - Assessment of student engagement and the "maturity of discipleship" observed.

            5. **FINANCIAL STEWARDSHIP & ACCOUNTABILITY**
               - Reflect on budget utilization as a matter of "values and accountability."
               - Evaluate value for money in terms of ministry impact.

            6. **CLASSROOM-LEVEL IMPACT ANALYSIS**
               - Analyze which class groups were most receptive (souls by class).
               - Identify patterns in student engagement across different age groups or classes.
               - Highlight any classes with zero decisions and hypothesize why.

            7. **OPERATIONAL INSIGHTS & STRATEGIC RECOMMENDATIONS**
               - Detailed "Key Learnings" for future missions.
               - Specific, actionable recommendations for leadership to improve mission effectiveness.
               - Red flags or missed opportunities that the data reveals (e.g., high withdrawal rate, low capacity utilization, classes with no coverage).
               - Cost-per-soul and cost-per-session efficiency metrics if financial data is available.

            8. **HISTORICAL CONTEXT & SCHOOL TRAJECTORY**
               - If previous missions exist at this school, compare the current mission against them (souls, team size, sessions).
               - Identify trends: is impact growing, plateauing, or declining?
               - If this is the first mission, note the significance of establishing a new relationship with the school.
               - If the previous mission's executive summary is provided, reference its recommendations and assess whether they were acted upon. Highlight progress made and areas that remain unaddressed.

            9. **DOCUMENTATION & FOLLOW-UP READINESS**
               - Comment on the completeness of mission documentation (photos, videos, debrief notes, session notes).
               - Recommend any missing documentation that should be gathered retroactively.

            **TONE & STYLE:**
            - Professional, inspiring, and data-driven.
            - Honest about challenges while celebrating spiritual victories.
            - Elaborate and thorough (do not limit to a short word count).
            - Surface non-obvious insights: ratios, comparisons, and anomalies that stakeholders might miss when looking at raw numbers.

            **OUTPUT FORMAT:**
            - Respond in clean Markdown that renders directly in a web UI.
            - Use `##` for section headers and `###` for subsections. Do NOT use `#` (h1).
            - Use `**bold**` for emphasis and key figures.
            - Use `-` for bullet points and `1.` for ordered lists.
            - Use `>` blockquotes for standout insights or constitutional references.
            - Use markdown tables (`| Column | Column |`) for comparative data (e.g., budget breakdowns, historical trends).
            - Do NOT wrap the output in a code block or use ``` fences. Output raw markdown only.
            - Do NOT include a top-level title — the UI already provides one.
            EOT;

        // Enhanced team analysis
        $approvedMembers = $mission->missionSubscriptions->where('status', PRFMissionSubscriptionStatus::APPROVED->value);
        $teamByRole = $approvedMembers->groupBy('mission_role')->map(function ($members, $role) {
            $roleName = $role ? PRFMissionRole::fromValue($role)->getLabel() : 'Unknown';

            return $roleName.' ('.$members->count().')';
        })->implode(', ');

        $attendeesList = $mission->missionSubscriptions->map(function ($subscription) {
            $status = $subscription->mission_subscription_status?->getLabel() ?? 'Unknown';
            $role = $subscription->mission_role ? PRFMissionRole::fromValue($subscription->mission_role)->getLabel() : 'Unknown';
            $name = $subscription->member?->full_name ?? 'Unknown Member';

            return "{$name} - {$role} [{$status}]";
        })->implode("\n");

        // Enhanced expense analysis from requisitions
        $expenseBreakdown = '';
        if ($mission->requisitions->isNotEmpty()) {
            $expensesByCategory = $mission->requisitions
                ->flatMap(fn ($req) => $req->requisitionItems)
                ->groupBy('expenseCategory.name')
                ->map(function ($items, $category) {
                    $total = $items->sum('amount');
                    $count = $items->count();

                    return "- {$category}: KES ".number_format($total)." ({$count} items)";
                })
                ->implode("\n");
            $expenseBreakdown = $expensesByCategory ?: 'No expenses recorded';
        } else {
            $expenseBreakdown = 'No expenses recorded';
        }

        // Souls analysis by decision type
        $soulsBreakdown = $mission->souls->isNotEmpty()
            ? $mission->souls->groupBy('decision_type')->map(function ($souls, $type) {
                $typeName = $type ? PRFSoulDecisionType::fromValue($type)->getLabel() : 'Unknown';

                return "- {$typeName}: {$souls->count()}";
            })->implode("\n")
            : 'No souls recorded';

        // Questions analysis
        $questionsAnalysis = $mission->missionQuestions->isNotEmpty()
            ? "Total Questions: {$mission->missionQuestions->count()}"
            : 'No questions recorded';

        // Mission sessions analysis
        $sessionsAnalysis = '';
        if ($mission->missionSessions->isNotEmpty()) {
            $totalSessions = $mission->missionSessions->count();
            $facilitators = $mission->missionSessions->pluck('facilitator.full_name')->filter()->unique()->implode(', ');
            $speakers = $mission->missionSessions->pluck('speaker.full_name')->filter()->unique()->implode(', ');

            $sessionsAnalysis = "Total Sessions: {$totalSessions}\n";
            $sessionsAnalysis .= 'Facilitators: '.($facilitators ?: 'Not assigned')."\n";
            $sessionsAnalysis .= 'Speakers: '.($speakers ?: 'Not assigned');
        } else {
            $sessionsAnalysis = 'No sessions recorded';
        }

        // Souls by class group
        $soulsByClass = '';
        if ($mission->souls->isNotEmpty()) {
            $soulsByClass = $mission->souls->groupBy(fn ($soul) => $soul->classGroup?->name ?? 'Unknown')->map(function ($souls, $className) {
                $decisionBreakdown = $souls->groupBy('decision_type')->map(function ($group, $type) {
                    return PRFSoulDecisionType::fromValue($type)->getLabel().': '.$group->count();
                })->implode(', ');

                return "- {$className}: {$souls->count()} total ({$decisionBreakdown})";
            })->implode("\n");
        } else {
            $soulsByClass = 'No souls recorded';
        }

        // Session detail per class group
        $sessionDetails = '';
        if ($mission->missionSessions->isNotEmpty()) {
            $sessionDetails = $mission->missionSessions->sortBy('order')->map(function ($session) {
                $classGroup = $session->classGroup?->name ?? 'Unassigned';
                $facilitator = $session->facilitator?->full_name ?? 'Unassigned';
                $speaker = $session->speaker?->full_name ?? 'Unassigned';
                $time = $session->starts_at && $session->ends_at
                    ? "{$session->starts_at} - {$session->ends_at}"
                    : 'Time not set';
                $notes = $session->notes ? " | Notes: {$session->notes}" : '';

                return "- Session #{$session->order}: {$classGroup} | Facilitator: {$facilitator} | Speaker: {$speaker} | {$time}{$notes}";
            })->implode("\n");
        }

        // Subscription status breakdown (attrition analysis)
        $subscriptionStatusBreakdown = $mission->missionSubscriptions
            ->groupBy(fn ($sub) => $sub->mission_subscription_status?->getLabel() ?? 'Unknown')
            ->map(fn ($group, $label) => "- {$label}: {$group->count()}")
            ->implode("\n");

        // Team professional diversity (marketplace skills)
        $approvedSubs = $mission->missionSubscriptions
            ->where('status', PRFMissionSubscriptionStatus::APPROVED->value);
        $professions = $approvedSubs->map(fn ($sub) => $sub->member?->profession?->name)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->map(fn ($count, $profession) => "- {$profession}: {$count}")
            ->implode("\n");
        $professions = $professions ?: 'No profession data available';

        // Team church diversity (interdenominational insight)
        $churchDiversity = $approvedSubs->map(fn ($sub) => $sub->member?->church?->name)
            ->filter()
            ->countBy()
            ->sortDesc()
            ->map(fn ($count, $church) => "- {$church}: {$count}")
            ->implode("\n");
        $churchDiversity = $churchDiversity ?: 'No church data available';

        // Gender balance
        $genderBreakdown = $approvedSubs->map(fn ($sub) => $sub->member?->gender)
            ->filter()
            ->countBy()
            ->map(fn ($count, $gender) => "- {$gender}: {$count}")
            ->implode("\n");
        $genderBreakdown = $genderBreakdown ?: 'No gender data available';

        // School context
        $schoolContext = 'Name: '.($mission->school?->name ?? 'Unknown')."\n";
        $schoolContext .= 'Total Student Population: '.($mission->school?->total_students ?? 'Unknown')."\n";
        $schoolContext .= 'Address: '.($mission->school?->address ?? 'Not specified')."\n";
        $schoolContext .= 'Institution Type: '.($mission->school?->institution_type ?? 'Unknown');

        // Documentation completeness
        $photosCount = $mission->mission_photos_count ?? 0;
        $videosCount = $mission->mission_videos_count ?? 0;
        $debriefCount = $mission->debriefNotes->count();
        $sessionNotesCount = $mission->missionSessions->filter(fn ($s) => filled($s->notes))->count();
        $totalSessions = $mission->missionSessions->count();
        $documentationSummary = "Photos: {$photosCount}, Videos: {$videosCount}, Debrief Notes: {$debriefCount}, Sessions with Notes: {$sessionNotesCount}/{$totalSessions}";

        // Offline members
        $offlineCount = $mission->offlineMembers()->count();

        // Mission status and completion insights
        $statusLabel = PRFMissionStatus::fromValue($mission->status)->getLabel();
        $subscriptionRate = $mission->capacity > 0 ? round(($mission->missionSubscriptions->count() / $mission->capacity) * 100, 1) : 0;

        // Budget efficiency calculation from accounting event
        $budgetEfficiency = '';
        $budgetVariance = '';
        if ($mission->accountingEvent) {
            $accountingEvent = $mission->accountingEvent;
            $allocated = $accountingEvent->allocationEntries->sum('amount') ?? 0;
            $refunded = $accountingEvent->refunds->sum('amount') ?? 0;
            $spent = $allocated - $refunded;
            $utilization = $allocated > 0 ? round(($spent / $allocated) * 100, 1) : 0;
            $budgetEfficiency = "Budget Utilization: {$utilization}% (KES ".number_format($spent).' of KES '.number_format($allocated).')';
        } else {
            $budgetEfficiency = 'No financial data available';
        }

        // Budget vs Actual analysis
        if ($mission->school?->budgetEstimates?->isNotEmpty()) {
            $budgeted = $mission->school->budgetEstimates
                ->flatMap(fn ($estimate) => $estimate->budgetEstimateEntries)
                ->sum('amount');

            $actual = $mission->accountingEvent
                ? ($mission->accountingEvent->allocationEntries->sum('amount') ?? 0) - ($mission->accountingEvent->refunds->sum('amount') ?? 0)
                : 0;

            $variance = $budgeted - $actual;
            $variancePercent = $budgeted > 0 ? round(($variance / $budgeted) * 100, 1) : 0;
            $status = $variance >= 0 ? 'UNDER BUDGET' : 'OVER BUDGET';

            $budgetVariance = "Budget vs Actual:\n";
            $budgetVariance .= '- Budgeted: KES '.number_format($budgeted)."\n";
            $budgetVariance .= '- Actual Spent: KES '.number_format($actual)."\n";
            $budgetVariance .= '- Variance: KES '.number_format(abs($variance))." ({$status} - {$variancePercent}%)";
        } else {
            $budgetVariance = 'No budget estimates available for comparison';
        }

        // Format debrief notes and questions
        $debriefNotes = $mission->debriefNotes->isNotEmpty()
            ? $mission->debriefNotes->map(fn ($note) => "- {$note->note}")->implode("\n")
            : 'No debrief notes recorded';

        $missionQuestions = $mission->missionQuestions->isNotEmpty()
            ? $mission->missionQuestions->map(fn ($question) => "- {$question->question}")->implode("\n")
            : 'No questions recorded';

        // Format additional context
        $missionPrepNotes = $mission->mission_prep_notes ?: 'None provided';
        $dressingRecommendations = $mission->dressing_recommendations ?: 'None specified';
        $activityRecommendations = $mission->activity_recommendations ?: 'None specified';
        $weatherRecommendations = is_array($mission->weather_recommendations)
            ? implode(', ', $mission->weather_recommendations)
            : ($mission->weather_recommendations ?: 'None specified');

        $subscriptions = $mission->missionSubscriptions->where('status', PRFMissionSubscriptionStatus::APPROVED->value)->count();

        $userPrompt = <<<EOT
            **MISSION DETAILS**
            Mission Type: {$mission->missionType?->name}
            School Term: {$mission->schoolTerm?->name}
            Theme: {$mission->theme}
            Status: {$statusLabel}

            **SCHOOL CONTEXT**
            {$schoolContext}

            **SCHEDULING**
            Start: {$mission->start_date} at {$mission->start_time}
            End: {$mission->end_date} at {$mission->end_time}

            **TEAM DEPLOYMENT**
            Capacity Requested: {$mission->capacity} missionaries
            Approved Subscriptions: {$subscriptions} ({$subscriptionRate}% of capacity)
            Offline Members (non-app): {$offlineCount}
            Team Composition by Role: {$teamByRole}

            Subscription Status Breakdown:
            {$subscriptionStatusBreakdown}

            Detailed Attendance:
            {$attendeesList}

            **MARKETPLACE SKILLS & PROFESSIONAL DIVERSITY**
            Team Professions:
            {$professions}

            **INTERDENOMINATIONAL DIVERSITY**
            Team Churches:
            {$churchDiversity}

            **TEAM GENDER BALANCE**
            {$genderBreakdown}

            **IMPACT & OUTCOMES**
            Total Souls Won: {$mission->souls->count()}

            Souls by Decision Type:
            {$soulsBreakdown}

            Souls by Class Group:
            {$soulsByClass}

            **FINANCIAL STEWARDSHIP**
            {$budgetEfficiency}

            {$budgetVariance}

            Expense Breakdown by Category:
            {$expenseBreakdown}

            **SESSION DETAILS**
            {$sessionsAnalysis}

            Session-by-Session Breakdown:
            {$sessionDetails}

            **STUDENT ENGAGEMENT**
            {$questionsAnalysis}

            Questions from Students:
            {$missionQuestions}

            **DEBRIEF INSIGHTS**
            Team Feedback:
            {$debriefNotes}

            **HISTORICAL MISSIONS AT THIS SCHOOL**
            {$historicalSummary}

            **PREVIOUS MISSION'S EXECUTIVE SUMMARY**
            {$previousExecutiveSummary}

            **DOCUMENTATION COMPLETENESS**
            {$documentationSummary}

            **ADDITIONAL CONTEXT**
            Mission Preparation Notes: {$missionPrepNotes}
            Dressing Recommendations: {$dressingRecommendations}
            Activity Recommendations: {$activityRecommendations}
            Weather Recommendations: {$weatherRecommendations}
            EOT;

        $response = $this->runPrompt(
            systemPrompt: $systemPrompt,
            userPrompt: $userPrompt,
        );

        Log::info('Generated executive summary', [
            'mission_id' => $mission->id,
            'response_length' => strlen($response),
        ]);

        Mission::withoutEvents(function () use ($mission, $response): void {
            Mission::where('id', $mission->id)->update([
                'executive_summary' => $response,
            ]);
        });

        Log::info('Executive summary persisted', [
            'mission_id' => $mission->id,
        ]);
    }

    private function runPrompt(string $systemPrompt, string $userPrompt): string
    {
        $model = config('prf.app.gemini.model');

        $response = Http::withHeaders([
            'content-type' => 'application/json',
        ])
            ->timeout(60 * 4 * 4)
            ->withQueryParameters([
                'key' => config('prf.app.gemini.api_key'),

            ])->post(
                "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                [
                                    'text' => 'SYSTEM INSTRUCTION: '.$systemPrompt,
                                ],
                                [
                                    'text' => $userPrompt,
                                ],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => config('prf.app.gemini.max_output_tokens'),
                    ],
                ]
            );

        if ($response->failed()) {
            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Throw exception for rate limits to trigger retry with backoff
            if ($response->status() === 429) {
                throw new \RuntimeException('Gemini API rate limit exceeded. Will retry with backoff.');
            }

            // For other errors, throw so the job fails properly
            throw new \RuntimeException('Gemini API error: '.$response->status());
        }

        return $response->json()['candidates'][0]['content']['parts'][0]['text'];
    }
}
