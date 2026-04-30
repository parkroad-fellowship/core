<?php

namespace Database\Seeders;

use App\Models\ContactType;
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
            ContactType::factory()
                ->create([
                    'name' => $contactType,
                ]);
        }
    }
}
