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
        ]);

        $systemPrompt = <<<'EOT'
            You are about to generate an executive summary for the mission with the following details:
            
            Mission Type
            Mission Start Date
            Mission Start Time
            Mission End Date
            Mission End Time
            Missioners Requested
            School
            School Term
            School Capacity
            Souls Won
            Member Subscriptions
            Attendees
            Amount Disbursed
            Amount Spent
            Amount Refunded
            Fully Refunded
            Line Items
            Debrief Notes
            Questions from the learners

            Go over the information provided and summarize the mission in a clear and concise manner for a quick review by the executive team.

            Notes
            - Just give the summary, don't include any additional information or explanations.
            - Include a summary of the debrief notes and questions from the learners.
            EOT;

        $attendees = $mission->missionSubscriptions->map(function ($subscription) {
            return $subscription->member->full_name;
        })->implode(', ');

        $expenseItems = $mission->missionExpense->expenses->map(function ($expense) {
            return "- {$expense->expenseCategory->name}: {$expense->amount}";
        })->implode("\n");

        $debriefNotes = $mission->debriefNotes->map(function ($note) {
            return "- {$note->note}";
        })->implode("\n");

        $missionQuestions = $mission->missionQuestions->map(function ($question) {
            return "- {$question->question}";
        })->implode("\n");

        $userPrompt = <<<EOT
            Mission Type: {$mission->missionType->name}
            Mission Start Date: {$mission->start_date}
            Mission Start Time: {$mission->start_time}
            Mission End Date: {$mission->end_date}
            Mission End Time: {$mission->end_time}
            Missioners Requested: {$mission->capacity}
            School: {$mission->school->name}
            School Term: {$mission->schoolTerm->name}
            School Capacity: {$mission->school->total_students}
            Souls Won: {$mission->souls->count()}
            Member Subscriptions: {$mission->missionSubscriptions->count()}
            Attendees: {$attendees}
            Amount Disbursed: {$mission->missionExpense->amount_received}
            Amount Spent: {$mission->missionExpense->amount_spent}
            Amount Refunded: {$mission->missionExpense->amount_refunded}
            Fully Refunded: {$mission->missionExpense->is_refunded}
            Line Items:
            {$expenseItems}
            Debrief Notes:
            {$debriefNotes}
            Questions from the learners:
            {$missionQuestions}
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
