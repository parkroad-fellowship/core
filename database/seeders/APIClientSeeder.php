<?php

namespace Database\Seeders;

use App\Models\APIClient;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class APIClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            [
                'name' => 'PRF Missions',
                'app_id' => 'prf_missions_01khyfbrbnaqq8tjdcvjjnvv78',
                'allowed_roles' => ['member'],
            ],
            [
                'name' => 'PRF Leadership',
                'app_id' => 'prf_leadership_01khyfcbxn1mrwvg1yte0e7hq0',
                'allowed_roles' => [
                    'chairperson',
                    'vice chairperson',
                    'organising secretary',
                    'missions secretary',
                    'follow-up secretary',
                    'treasurer',
                    'prayer secretary',
                    'music secretary',
                    'missions committee member',
                    'camp committee member',
                ],
            ],
            [
                'name' => 'PRF Students',
                'app_id' => 'prf_students_01khyfckvkwmb6p5xw4hk7zfjh',
                'allowed_roles' => ['student'],
            ],
        ];

        foreach ($clients as $client) {
            APIClient::updateOrCreate(
                ['app_id' => $client['app_id']],
                [
                    'name' => $client['name'],
                    'secret' => Str::random(64),
                    'is_active' => true,
                    'allowed_roles' => $client['allowed_roles'],
                ],
            );
        }
    }
}
