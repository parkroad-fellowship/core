<?php

use App\Models\Media;
use App\Models\Mission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    config(['media-library.disk_name' => 'public', 'media-library.queue_conversions_by_default' => false]);
});

function missionPhoto(Mission $mission, array $customProperties = []): Media
{
    /** @var Media */
    return $mission
        ->addMedia(UploadedFile::fake()->image('photo.jpg'))
        ->withCustomProperties($customProperties)
        ->toMediaCollection(Mission::MISSION_PHOTOS);
}

describe('deleteMedia', function () {
    it('deletes media that belongs to the mission', function () {
        $mission = Mission::factory()->create();
        $media = missionPhoto($mission);

        actingAsTenantUser()
            ->deleteJson(route('v2.api.missions.delete-media', [$mission->ulid, $media->uuid]))
            ->assertNoContent();

        expect(Media::query()->whereKey($media->id)->exists())->toBeFalse();
    });

    it('never deletes media that belongs to another record', function () {
        $mission = Mission::factory()->create();
        $otherMedia = missionPhoto(Mission::factory()->create());

        actingAsTenantUser()
            ->deleteJson(route('v2.api.missions.delete-media', [$mission->ulid, $otherMedia->uuid]))
            ->assertNotFound();

        expect(Media::query()->whereKey($otherMedia->id)->exists())->toBeTrue();
    });

    it('forbids deleting media uploaded by someone else without edit rights', function () {
        $mission = Mission::factory()->create();
        $media = missionPhoto($mission);

        actingAsTenantUser(['member'])->deleteJson(route('v2.api.missions.delete-media', [
            $mission->ulid,
            $media->uuid,
        ]))->assertForbidden();
    });

    it('lets the uploader delete their own media', function () {
        $mission = Mission::factory()->create();
        $request = actingAsTenantUser(['member']);
        $media = missionPhoto($mission, [Media::UPLOADED_BY_PROPERTY => Auth::user()->ulid]);

        $request->deleteJson(route('v2.api.missions.delete-media', [$mission->ulid, $media->uuid]))->assertNoContent();
    });
});

describe('getMedia', function () {
    it('lists media for the requested collections', function () {
        $mission = Mission::factory()->create();
        missionPhoto($mission);

        actingAsTenantUser()
            ->getJson(route('api.missions.get-media', [$mission->ulid, 'collections' => Mission::MISSION_PHOTOS]))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('rejects unknown collections', function () {
        $mission = Mission::factory()->create();

        actingAsTenantUser()
            ->getJson(route('api.missions.get-media', [$mission->ulid, 'collection' => 'not-a-collection']))
            ->assertJsonValidationErrors('collections.0');
    });
});

it('protects every v2 route with the tenant middleware stack', function () {
    $v2Routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn($route) => str_starts_with($route->getName() ?? '', 'v2.api.'));

    expect($v2Routes)->not->toBeEmpty();

    $v2Routes->each(
        fn($route) => expect($route->gatherMiddleware())
            ->toContain('tenant.initialized', 'auth:sanctum', 'tenant.validate'),
    );
});
