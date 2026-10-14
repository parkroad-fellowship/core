<?php

use App\Enums\PRFActiveStatus;
use App\Filament\Forms\Schemas\SchoolSchema;
use App\Models\ContactType;
use App\Models\School;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    // Adding a school queues a route calculation to the Maps API.
    Queue::fake();
});

it('finds schools with a similar name, including inactive ones', function () {
    $active = School::factory()->create(['name' => "St. Mary's Girls High School"]);
    $inactive = School::factory()->create(['name' => 'St Marys Boys', 'is_active' => PRFActiveStatus::INACTIVE]);
    $other = School::factory()->create(['name' => 'Moi Forces Academy']);

    $similar = SchoolSchema::similarSchools('st marys');

    expect($similar->pluck('id')->all())->toContain($active->id, $inactive->id)->not->toContain($other->id);
});

it('adds a school with its first contact', function () {
    ContactType::factory()->create();

    $school = SchoolSchema::createFromQuickForm(SchoolSchema::quickCreateDefaults([
        'name' => 'Kilimani Primary',
        'total_students' => 450,
        'location' => ['lat' => -1.29, 'lng' => 36.78],
        'address' => 'Kilimani, Nairobi',
        'contact_name' => 'Jane Wanjiku',
        'contact_phone' => '+254712345678',
    ]));

    expect($school->name)
        ->toBe('Kilimani Primary')
        ->and($school->is_active)
        ->toBe(PRFActiveStatus::ACTIVE)
        ->and($school->schoolContacts()->count())
        ->toBe(1);
});
