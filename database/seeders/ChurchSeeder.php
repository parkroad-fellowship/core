<?php

namespace Database\Seeders;

use App\Models\Church;
use Illuminate\Database\Seeder;

class ChurchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $churches = [
            'RCCG',
            'Life Purpose',
        ];

        foreach ($churches as $church) {
            Church::factory()
                ->create([
                    'name' => $church,
                ]);
        }
    }
}
