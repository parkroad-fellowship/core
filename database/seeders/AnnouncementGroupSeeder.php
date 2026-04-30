<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Group;
use Illuminate\Database\Seeder;

class AnnouncementGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Group::cursor() as $group) {
            $random = rand(1, 3);
            $group->announcementGroups()->createMany(
                Announcement::inRandomOrder()->limit($random)->get()->map(function ($announcement) {
                    return [
                        'announcement_id' => $announcement->id,
                    ];
                })->toArray()
            );
        }
    }
}
