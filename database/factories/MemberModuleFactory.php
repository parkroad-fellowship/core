<?php

namespace Database\Factories;

use App\Enums\PRFCompletionStatus;
use App\Models\Course;
use App\Models\Member;
use App\Models\MemberModule;
use App\Models\Module;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberModule>
 */
class MemberModuleFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => $this->existingOrNew(Course::class),
            'module_id' => $this->existingOrNew(Module::class),
            'member_id' => $this->existingOrNew(Member::class),
            'percent_complete' => 0,
            'completion_status' => PRFCompletionStatus::cases()[0],
        ];
    }
}
