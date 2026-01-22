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
     * Create a new job instance.
     */
    public function __construct(
        public Mission $mission,
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mission = $this->mission;
        $mission->load([
            'missionType',
            'school',
            'schoolTerm',
            'missionSubscriptions.member',
            'debriefNotes',
            'missionQuestions',
            'souls',
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
        ]);

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

            6. **OPERATIONAL INSIGHTS & STRATEGIC RECOMMENDATIONS**
               - Detailed "Key Learnings" for future missions.
               - Specific, actionable recommendations for leadership to improve mission effectiveness.

            **TONE & STYLE:**
            - Professional, inspiring, and data-driven.
            - Honest about challenges while celebrating spiritual victories.
            - Elaborate and thorough (do not limit to a short word count).
            - Use formatting (bolding, headers) for high scannability.
            EOT;

        // Enhanced team analysis
        $approvedMembers = $mission->missionSubscriptions->where('mission_subscription_status.value', 2);
        $teamByRole = $approvedMembers->groupBy('mission_role')->map(function ($members, $role) {
            $roleName = PRFMissionRole::fromValue($role)->getLabel();

            return $roleName.' ('.$members->count().')';
        })->implode(', ');

        $attendeesList = $mission->missionSubscriptions->map(function ($subscription) {
            $status = $subscription->mission_subscription_status->getLabel();
            $role = PRFMissionRole::fromValue($subscription->mission_role)->getLabel();

            return "{$subscription->member->full_name} - {$role} [{$status}]";
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
        $soulsBreakdown = $mission->souls->groupBy('decision_type')->map(function ($souls, $type) {
            $typeName = PRFSoulDecisionType::fromValue($type)->getLabel();

            return "- {$typeName}: {$souls->count()}";
        })->implode("\n");

        // Questions analysis by category and difficulty
        $questionsAnalysis = '';
        if ($mission->missionQuestions->isNotEmpty()) {
            $questionsByCategory = $mission->missionQuestions->groupBy('category')->map(function ($questions, $category) {
                return ucfirst($category ?? 'General').' ('.$questions->count().')';
            })->implode(', ');

            $difficultQuestions = $mission->missionQuestions->whereIn('difficulty_level', ['advanced', 'complex'])->count();
            $unansweredQuestions = $mission->missionQuestions->where('was_answered', false)->count();

            $questionsAnalysis = "Categories: {$questionsByCategory}\n";
            $questionsAnalysis .= "Complex/Advanced Questions: {$difficultQuestions}\n";
            $questionsAnalysis .= "Unanswered Questions: {$unansweredQuestions}";
        } else {
            $questionsAnalysis = 'No questions recorded';
        }

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
        if ($mission->school->budgetEstimates->isNotEmpty()) {
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
        $debriefNotes = $mission->debriefNotes->map(function ($note) {
            return "- {$note->note}";
        })->implode("\n");

        $missionQuestions = $mission->missionQuestions->map(function ($question) {
            return "- {$question->question}";
        })->implode("\n");

        // Format additional context
        $missionPrepNotes = $mission->mission_prep_notes ?: 'None provided';
        $dressingRecommendations = $mission->dressing_recommendations ?: 'None specified';
        $activityRecommendations = $mission->activity_recommendations ?: 'None specified';
        $weatherRecommendations = $mission->weather_recommendations ?: 'None specified';

        $subscriptions = $mission->missionSubscriptions->where('mission_subscription_status', PRFMissionSubscriptionStatus::APPROVED)->count();

        $userPrompt = <<<EOT
            **MISSION DETAILS**
            Mission Type: {$mission->missionType->name}
            School: {$mission->school->name}
            School Term: {$mission->schoolTerm->name}
            Theme: {$mission->theme}
            
            **SCHEDULING**
            Start: {$mission->start_date} at {$mission->start_time}
            End: {$mission->end_date} at {$mission->end_time}
            Status: {$statusLabel}
            
            **TEAM DEPLOYMENT**
            Capacity Requested: {$mission->capacity} missionaries
            Subscriptions: {$subscriptions} ({$subscriptionRate}% of capacity)
            Team Composition: {$teamByRole}
            
            Detailed Attendance:
            {$attendeesList}
            
            **IMPACT & OUTCOMES**
            Total Souls Won: {$mission->souls->count()}
            
            Souls Breakdown:
            {$soulsBreakdown}
            
            **FINANCIAL STEWARDSHIP**
            {$budgetEfficiency}
            
            {$budgetVariance}
            
            Expense Breakdown:
            {$expenseBreakdown}
            
            **OPERATIONAL INSIGHTS**
            {$sessionsAnalysis}
            
            **STUDENT ENGAGEMENT**
            {$questionsAnalysis}
            
            Questions from Students:
            {$missionQuestions}
            
            **DEBRIEF INSIGHTS**
            Team Feedback:
            {$debriefNotes}
            
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
            'response' => $response,
        ]);

        $mission->update([
            'executive_summary' => $response,
        ]);
    }

    private function runPrompt(string $systemPrompt, string $userPrompt): string
    {
        $model = config('prf.app.gemini.model');

        $response = Http::withHeaders([
            'content-type' => 'application/json',
        ])
            ->timeout(60 * 4)
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

            return 'Error generating summary.';
        }

        return $response->json()['candidates'][0]['content']['parts'][0]['text'];
    }
}
