<?php

namespace Database\Seeders;

use App\Models\Gift;
use Illuminate\Database\Seeder;

class GiftSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gifts = [
            'Singing',
            'Dancing',
            'Preaching',
            'Teaching',

        ];

        foreach ($gifts as $gift) {
            Gift::factory()
                ->create([
                    'name' => $gift,
                ]);
        }
    }
}
