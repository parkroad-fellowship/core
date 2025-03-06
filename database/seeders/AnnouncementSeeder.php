<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $announcements = [
            [
                'title' => 'Announcement 1',
                'content' => 'This is the content of announcement 1',
                'published_at' => now()->addDays(10),
            ],
            [
                'title' => 'Announcement 2',
                'content' => 'This is the content of announcement 2',
                'published_at' => now()->addDays(3),
            ],
            [
                'title' => 'Announcement 3',
                'content' => 'This is the content of announcement 3',
                'published_at' => now()->addDays(10),
            ],
        ];
        foreach ($announcements as $announcement) {
            Announcement::create($announcement);
        }
    }
}
