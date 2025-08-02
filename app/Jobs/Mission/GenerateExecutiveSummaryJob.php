<?php

namespace App\Jobs\Mission;

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
            'missionExpense.expenses.expenseCategory',
            'debriefNotes',
            'missionQuestions',
            'souls',
            'missionSessions.facilitator',
            'missionSessions.speaker',
            'missionSessions.classGroup',
        ]);

        $systemPrompt = <<<'EOT'
            You are an executive assistant tasked with creating a comprehensive mission executive summary for Parkroad Fellowship's leadership team. This summary will be used for strategic decision-making, mission effectiveness evaluation, and organizational learning.

            **MISSION CONTEXT & PURPOSE:**
            Parkroad Fellowship conducts evangelistic missions to secondary schools and institutions across Kenya. Each mission involves careful planning, resource allocation, team deployment, and follow-up activities. Your summary should provide actionable insights for continuous improvement.

            **SUMMARY STRUCTURE & REQUIREMENTS:**

            1. **MISSION OVERVIEW** (2-3 sentences)
               - Mission type, location, dates, and primary objective
               - Current status and overall success indicators

            2. **TEAM DEPLOYMENT & PARTICIPATION**
               - Team composition by roles (leaders, trainers, music, transportation)
               - Subscription vs. actual attendance analysis
               - Notable team performance insights

            3. **IMPACT & OUTCOMES**
               - Souls won by decision type (salvation, rededication, camp, prayer)
               - Student engagement and response quality
               - Long-term impact potential

            4. **FINANCIAL STEWARDSHIP**
               - Budget efficiency (planned vs. actual expenditure)
               - Key expense categories and value for money
               - Financial accountability status

            5. **OPERATIONAL INSIGHTS**
               - Session effectiveness and facilitator performance
               - Question complexity and theological engagement level
               - Logistical successes and challenges

            6. **STRATEGIC RECOMMENDATIONS**
               - Key learnings for future missions
               - Areas requiring leadership attention
               - Follow-up actions needed

            **TONE & STYLE:**
            - Professional yet inspiring
            - Data-driven with human impact focus
            - Action-oriented for leadership decision-making
            - Honest about challenges while celebrating victories
            - Maximum 300-400 words

            **KEY PRINCIPLES:**
            - Every detail serves the greater mission of advancing God's kingdom
            - Financial stewardship reflects our values
            - Team development is as important as immediate outcomes
            - Learning from each mission improves our overall effectiveness
            EOT;

        // Enhanced team analysis
        $approvedMembers = $mission->missionSubscriptions->where('mission_subscription_status.value', 2);
        $teamByRole = $approvedMembers->groupBy('mission_role')->map(function ($members, $role) {
            $roleName = \App\Enums\PRFMissionRole::fromValue($role)->getLabel();

            return $roleName.' ('.$members->count().')';
        })->implode(', ');

        $attendeesList = $mission->missionSubscriptions->map(function ($subscription) {
            $status = $subscription->mission_subscription_status->getLabel();
            $role = \App\Enums\PRFMissionRole::fromValue($subscription->mission_role)->getLabel();

            return "{$subscription->member->full_name} - {$role} [{$status}]";
        })->implode("\n");

        // Enhanced expense analysis
        $expenseBreakdown = $mission->missionExpense?->expenses->groupBy('expenseCategory.name')->map(function ($expenses, $category) {
            $total = $expenses->sum('amount');
            $count = $expenses->count();

            return "- {$category}: KES ".number_format($total)." ({$count} items)";
        })->implode("\n") ?? 'No expenses recorded';

        // Souls analysis by decision type
        $soulsBreakdown = $mission->souls->groupBy('decision_type')->map(function ($souls, $type) {
            $typeName = \App\Enums\PRFSoulDecisionType::fromValue($type)->getLabel();

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
        $statusLabel = \App\Enums\PRFMissionStatus::fromValue($mission->status)->getLabel();
        $subscriptionRate = $mission->capacity > 0 ? round(($mission->missionSubscriptions->count() / $mission->capacity) * 100, 1) : 0;

        // Budget efficiency calculation
        $budgetEfficiency = '';
        if ($mission->missionExpense) {
            $disbursed = $mission->missionExpense->amount_received ?? 0;
            $spent = $mission->missionExpense->amount_spent ?? 0;
            $utilization = $disbursed > 0 ? round(($spent / $disbursed) * 100, 1) : 0;
            $budgetEfficiency = "Budget Utilization: {$utilization}% (KES ".number_format($spent).' of KES '.number_format($disbursed).')';
        } else {
            $budgetEfficiency = 'No financial data available';
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
            Subscriptions: {$mission->missionSubscriptions->count()} ({$subscriptionRate}% of capacity)
            Team Composition: {$teamByRole}
            
            Detailed Attendance:
            {$attendeesList}
            
            **IMPACT & OUTCOMES**
            School Capacity: {$mission->school->total_students} students
            Total Souls Won: {$mission->souls->count()}
            
            Souls Breakdown:
            {$soulsBreakdown}
            
            **FINANCIAL STEWARDSHIP**
            {$budgetEfficiency}
            
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
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $systemPrompt,
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

        Log::info('Generated executive summary', [
            'response' => $response,
        ]);

        return $response->json()['candidates'][0]['content']['parts'][0]['text'];
    }
}
