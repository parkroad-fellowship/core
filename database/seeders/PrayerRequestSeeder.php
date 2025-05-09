<?php

namespace Database\Seeders;

use App\Models\PrayerRequest;
use Illuminate\Database\Seeder;

class PrayerRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PrayerRequest::factory()->count(30)->create();
    }
}
