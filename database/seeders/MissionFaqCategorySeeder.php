<?php

namespace Database\Seeders;

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
        ];

        foreach ($categories as $category) {
            \App\Models\MissionFaqCategory::updateOrCreate(
                ['name' => $category],
                [
                    'name' => $category,
                ]
            );
        }
    }
}
