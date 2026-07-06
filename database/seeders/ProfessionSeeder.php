<?php

namespace Database\Seeders;

use App\Models\Profession;
use Illuminate\Database\Seeder;

class ProfessionSeeder extends Seeder
{
    public function run(): void
    {
        $professions = [
            'Accountant',
            'Administrator',
            'Advocate',
            'Automotive Engineer',
            'Banker',
            'Biotechnologist',
            'Business Analyst',
            'Business Owner',
            'Chef',
            'Civil Engineer',
            'Communication Consultant',
            'Computer Scientist',
            'Content Writer',
            'Counselor',
            'Customer Experience Manager',
            'Data Scientist',
            'Doctor',
            'Economist',
            'Engineer',
            'Entrepreneur',
            'Finance Professional',
            'Financial Advisor',
            'Healthcare Provider',
            'Hospitality Professional',
            'IT Specialist',
            'Journalist',
            'Lawyer',
            'Marketer',
            'Mechanical Engineer',
            'Minister',
            'Music Producer',
            'Paramedic',
            'Pastor',
            'Pharmacy Technician',
            'Program Manager',
            'Project Manager',
            'Property Consultant',
            'Property Manager',
            'Psychiatrist',
            'Psychologist',
            'Real Estate Agent',
            'Regulatory Compliance Expert',
            'Self Employed',
            'Social Worker',
            'Socioeconomist',
            'Sound Engineer',
            'Statistician',
            'Student',
            'Teacher',
            'Theologian',
            'Travel Advisor',
            'Veterinary Doctor',
        ];

        foreach ($professions as $profession) {
            Profession::factory()
                ->create([
                    'name' => $profession,
                ]);
        }
    }
}
