<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contactTypes = [
            'Head Teacher',
            'CU Patron',
            'Teacher',
        ];

        foreach ($contactTypes as $contactType) {
            \App\Models\ContactType::factory()
                ->create([
                    'name' => $contactType,
                ]);
        }
    }
}
