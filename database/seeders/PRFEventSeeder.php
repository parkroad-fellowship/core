<?php

namespace Database\Seeders;

use App\Models\PRFEvent;
use Illuminate\Database\Seeder;

class PRFEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            [
                'name' => 'AGM 2025',
                'description' => <<<'EOT'
                    Notice is hereby given of our Annual General Meeting (AGM) to be held on Saturday 1st  March 2025 at a venue to be communicated; from 9 am - 2 PM. We are hoping to borrow from the current practices where AGMs don't take long. We hope that you as our esteemed member will help us achieve this goal.

                    The following shall be the agenda as per Section 8 (b) (ii) of the Parkroad Fellowship constitution:

                    Attainment of quorum as per Section 8.3 of the Parkroad Fellowship Constitution and declaration of the start of the meeting by the Chairperson
                    Confirmation of Minutes of the AGM held on  24th February 2024
                    Matters Arising from the minutes of the AGM held on 24th February 2024
                    Reading, receipt, and consideration of the reports of the Executive Committee 2024/2025
                    Consideration of the accounts
                    The Future of Parkroad Fellowship
                    Reading, receipt, and consideration of the Board of Trustees report
                    Dissolution of the Executive Committee 2024/2025
                    Announcement of the newly elected Executive Committee by the Returning Officers
                    Appointment of the Honorary Auditor 2025
                    Commissioning of the 2025/2026 Executive Committee and the Honorary Auditor 2025
                    Any Other Business   
                    Should you want any other matter added to the Agenda of the AGM or for any issues and inquiries, please write to us via the Chairperson, or the Organizing Secretary through chairperson@example.org or organizingsec@example.org respectively.
                EOT,
                'start_date' => '2025-03-01',
                'end_date' => '2025-03-01',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',

            ],
            [
                'name' => 'Bible Study Fun Day',
                'description' => <<<'EOT'
                    This is a fun day for Bible study. We will be having fun activities such as games, music, and more.
                EOT,
                'start_date' => '2024-08-11',
                'end_date' => '2024-08-11',
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
            ],
        ];

        foreach ($events as $event) {
            PRFEvent::factory()->create($event);
        }
    }
}
