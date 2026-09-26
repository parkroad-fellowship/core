<?php

use App\Models\Department;

/*
 | Reference template for API resource tests (see .ai/guidelines/prf/testing.md).
 | Authorization and empty-payload validation for every resource are covered by
 | AuthorizationTest and ValidationTest; this file covers behaviour.
 */

describe('index', function () {
    it('lists departments', function () {
        Department::factory()->count(3)->create();

        actingAsTenantUser()
            ->getJson(route('api.departments.index'))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data' => ['*' => ['entity', 'ulid', 'name', 'is_active']]]);
    });

    it('sorts by name', function () {
        Department::factory()->create(['name' => 'Worship']);
        Department::factory()->create(['name' => 'Choir']);

        actingAsTenantUser()
            ->getJson(route('api.departments.index', ['sort' => 'name']))
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Choir');
    });
});

describe('store', function () {
    it('creates a department', function () {
        actingAsTenantUser()->postJson(route('api.departments.store'), [
            'name' => 'Choir',
        ])->assertSuccessful()->assertJsonPath('data.entity', 'department')->assertJsonPath('data.name', 'Choir');

        expect(Department::query()->where('name', 'Choir')->exists())->toBeTrue();
    });

    it('validates input', function (array $payload, string $field) {
        actingAsTenantUser()->postJson(route('api.departments.store'), $payload)->assertJsonValidationErrors($field);
    })->with([
        'missing name' => [[], 'name'],
        'name too long' => [['name' => str_repeat('a', 256)], 'name'],
    ]);
});

describe('show', function () {
    it('shows a department', function () {
        $department = Department::factory()->create();

        actingAsTenantUser()
            ->getJson(route('api.departments.show', $department->ulid))
            ->assertOk()
            ->assertJsonPath('data.ulid', $department->ulid)
            ->assertJsonPath('data.name', $department->name);
    });

    it('returns not found for an unknown ulid', function () {
        actingAsTenantUser()
            ->getJson(route('api.departments.show', strtolower((string) str()->ulid())))
            ->assertNotFound();
    });
});

describe('update', function () {
    it('updates a department', function () {
        $department = Department::factory()->create();

        actingAsTenantUser()->putJson(route('api.departments.update', $department->ulid), [
            'name' => 'Updated Name',
        ])->assertOk()->assertJsonPath('data.name', 'Updated Name');

        expect($department->fresh()->name)->toBe('Updated Name');
    });

    it('records the change in the activity log', function () {
        $department = Department::factory()->create(['name' => 'Before']);

        actingAsTenantUser()->putJson(route('api.departments.update', $department->ulid), ['name' => 'After']);

        expect($department->activitiesAsSubject()->where('event', 'updated')->exists())->toBeTrue();
    });
});

describe('destroy', function () {
    it('soft deletes a department', function () {
        $department = Department::factory()->create();

        actingAsTenantUser()->deleteJson(route('api.departments.destroy', $department->ulid))->assertNoContent();

        $this->assertSoftDeleted($department);
    });
});
