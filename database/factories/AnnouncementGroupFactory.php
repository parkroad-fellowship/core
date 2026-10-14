<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\AnnouncementGroup;
use App\Models\Group;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnouncementGroup>
 */
class AnnouncementGroupFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'announcement_id' => $this->existingOrNew(Announcement::class),
            'group_id' => $this->existingOrNew(Group::class),
        ];
    }
}
