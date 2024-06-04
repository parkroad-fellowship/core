<?php


it('should return a list of missions', function () {
    // Act
    $response = actingAsUser()->get(route('api.missions.index'));

    // Assert
    $response
        ->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'entity',
                    'ulid',
                    'start_date',
                    'end_date',
                    'capacity',
                    'status',
                    'mission_prep_notes',
                    'school_term',
                    'mission_type',
                    'school',
                ],
            ],
        ]);
});
