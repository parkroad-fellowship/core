<?php

namespace Database\Factories;

use App\Enums\PRFGender;
use App\Models\Church;
use App\Models\MaritalStatus;
use App\Models\Member;
use App\Models\Profession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'marital_status_id' => MaritalStatus::query()->inRandomOrder()->first()?->getKey(),
            'profession_id' => Profession::query()->inRandomOrder()->first()?->getKey(),
            'church_id' => Church::query()->inRandomOrder()->first()?->getKey(),
            'first_name' => 'Member',
            'last_name' => $this->faker->lastName(),
            'postal_address' => $this->faker->address(),
            'phone_number' => $this->faker->e164PhoneNumber(),
            'personal_email' => $this->faker->unique()->safeEmail(),
            'residence' => $this->faker->address(),
            'year_of_salvation' => $this->faker->numberBetween(1990, 2021),
            'church_volunteer' => $this->faker->boolean(),
            'pastor' => $this->faker->name(),
            'profession_institution' => $this->faker->company(),
            'profession_location' => $this->faker->address(),
            'profession_contact' => $this->faker->phoneNumber(),
            'accept_terms' => $this->faker->boolean(),
            'approved' => $this->faker->boolean(),
            'gender' => $this->faker->randomElement(PRFGender::getElements()),
            'bio' => $this->faker->paragraph(),
            'linked_in_url' => $this->faker->url(),
        ];
    }
}
