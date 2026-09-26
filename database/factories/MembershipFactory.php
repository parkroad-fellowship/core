<?php

namespace Database\Factories;

use App\Enums\PRFMembershipType;
use App\Models\Member;
use App\Models\Membership;
use App\Models\SpiritualYear;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $membershipType = $this->faker->randomElement(PRFMembershipType::getElements());

        $fees = match ($membershipType) {
            PRFMembershipType::FRIEND => 0,
            PRFMembershipType::YEARLY_MEMBER => 500,
            PRFMembershipType::LIFETIME_MEMBER => 5000,
            default => 0,
        };

        return [
            'member_id' => $this->existingOrNew(Member::class),
            'spiritual_year_id' => $this->existingOrNew(SpiritualYear::class),
            'type' => $membershipType,
            'approved' => $this->faker->boolean,
            'amount' => $fees,
        ];
    }
}
