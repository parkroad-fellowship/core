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
            'Nakuru High School',
            'Kenya High School',
            'Alliance High School',
            'Moi Forces Academy',
            'Mawingo Secondary School',
            'Kisumu Boys High School',
            'Bahati PCEA Secondary School',
            'Mangu High School',
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
