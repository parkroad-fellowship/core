<?php

namespace Database\Seeders;

use App\Models\MissionFaqCategory;
use Illuminate\Database\Seeder;

class MissionFaqCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Spiritual Maturity',
            'The Holy Spirit',
            'Cults',
            'Occults',
            'Technology & the digital space',
            'Relationships & Family',
            'Sexual Purity',
            'Academic Excellence',
            'Mental Health',
            'Drug & Substance Abuse',
        ];

        foreach ($categories as $category) {
            MissionFaqCategory::updateOrCreate(
                ['name' => $category],
                [
                    'name' => $category,
                ]
            );
        }
    }
}
