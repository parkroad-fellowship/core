<?php

namespace Database\Seeders;

use App\Enums\PRFMissionStatus;
use App\Jobs\Mission\CreateCohortJob;
use App\Models\Cohort;
use App\Models\Mission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CohortMissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Mission::query()
            ->where('status', PRFMissionStatus::SERVICED)
            ->cursor() as $mission) {

            CreateCohortJob::dispatchSync($mission);
        }
    }
}
