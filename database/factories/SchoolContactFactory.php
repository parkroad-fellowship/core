<?php

namespace Database\Factories;

use App\Models\ContactType;
use App\Models\School;
use App\Models\SchoolContact;
use Database\Factories\Concerns\ReusesExistingRecords;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolContact>
 */
class SchoolContactFactory extends Factory
{
    use ReusesExistingRecords;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_id' => $this->existingOrNew(School::class),
            'contact_type_id' => $this->existingOrNew(ContactType::class),
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->e164PhoneNumber(),
            'preferred_name' => $this->faker->firstName(),
        ];
    }
}
