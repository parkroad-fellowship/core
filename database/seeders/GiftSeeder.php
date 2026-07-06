<?php

namespace Database\Seeders;

use App\Models\Gift;
use Illuminate\Database\Seeder;

class GiftSeeder extends Seeder
{
    public function run(): void
    {
        $gifts = [
            'Administration',
            'Communication',
            'Counseling',
            'Dancing',
            'Discipleship',
            'Evangelism',
            'Hospitality',
            'Instrumentalist',
            'Intercessory',
            'Leadership',
            'Media',
            'Mentorship',
            'Missions',
            'Music Ministry',
            'Pastoral',
            'Prayer',
            'Preaching',
            'Prophetic',
            'Singing',
            'Sound',
            'Teaching',
            'Technical',
            'Ushering',
            'Worship',
            'Youth Ministry',
        ];

        foreach ($gifts as $gift) {
            Gift::factory()
                ->create([
                    'name' => $gift,
                ]);
        }
    }
}
