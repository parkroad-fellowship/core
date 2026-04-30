<?php

namespace Database\Seeders;

use App\Enums\PRFPromptFrequency;
use App\Models\PrayerPrompt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PrayerPromptSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $watchPrompts = [
            [
                'description' => 'Pray for the executive committee & the effectiveness of the fellowship',
                'day_of_week' => Carbon::MONDAY,
            ],
            [
                'description' => 'Pray for an area of life concerning the chairperson & vice chairperson',
                'day_of_week' => Carbon::TUESDAY,
            ],            [
                'description' => 'Pray for an area of life concerning the treasurer',
                'day_of_week' => Carbon::TUESDAY,
            ],
            [
                'description' => 'Pray for an area of life concerning the organising secretary',
                'day_of_week' => Carbon::WEDNESDAY,
            ],
            [
                'description' => 'Pray for an area of life concerning the music secretary',
                'day_of_week' => Carbon::WEDNESDAY,
            ],
            [
                'description' => 'Pray for an area of life concerning the missions and vice missions secretaries',
                'day_of_week' => Carbon::THURSDAY,
            ],
            [
                'description' => 'Pray for an area of life concerning the prayer & vice prayer secretaries',
                'day_of_week' => Carbon::THURSDAY,
            ],
            [
                'description' => 'Pray for an area of life concerning the follow-up secretary',
                'day_of_week' => Carbon::FRIDAY,
            ],
            [
                'description' => 'Pray for the schools we get to minister in',
                'day_of_week' => Carbon::FRIDAY,
            ],
            [
                'description' => 'Pray for the souls won that they may be established in the faith',
                'day_of_week' => Carbon::SATURDAY,
            ],
        ];

        foreach ($watchPrompts as $prompt) {
            PrayerPrompt::updateOrCreate([
                'description' => $prompt['description'],
                'frequency' => PRFPromptFrequency::WEEKLY,
                'day_of_week' => $prompt['day_of_week'],
            ]);

        }
    }
}
