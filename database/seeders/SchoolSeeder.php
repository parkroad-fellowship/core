<?php

namespace Database\Seeders;

use App\Models\ContactType;
use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = [
            'National High School',
            'C1 Senior School',
            'C2 Senior School',
        ];

        foreach ($schools as $school) {
            $school = School::factory()
                ->create(['name' => $school]);

            $contactTypes = ContactType::all();

            $school->schoolContacts()->createMany([
                [
                    'contact_type_id' => $contactTypes->random()->getKey(),
                    'name' => 'Cool Guy',
                    'phone' => '07012345678',
                ],
                [
                    'contact_type_id' => $contactTypes->random()->getKey(),
                    'name' => 'Jane Doe',
                    'phone' => '07012345679',
                ],
            ]);
        }
    }
}
