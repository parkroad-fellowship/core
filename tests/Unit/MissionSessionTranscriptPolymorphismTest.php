<?php

use App\Enums\PRFMorphType;
use App\Models\MissionQuestion;
use App\Models\MissionSession;
use App\Models\PRFEvent;
use App\Models\Transcript;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

it('resolves new morph types for transcript owners', function () {
    expect(PRFMorphType::fromValue(PRFMorphType::MISSION_SESSION->value))->toBe(PRFMorphType::MISSION_SESSION)
        ->and(PRFMorphType::fromValue(PRFMorphType::MISSION_QUESTION->value))->toBe(PRFMorphType::MISSION_QUESTION)
        ->and(PRFMorphType::MISSION_SESSION->getModel())->toBe(MissionSession::class)
        ->and(PRFMorphType::MISSION_QUESTION->getModel())->toBe(MissionQuestion::class)
        ->and(PRFMorphType::MISSION_SESSION->getName())->toBe('Mission Session')
        ->and(PRFMorphType::MISSION_QUESTION->getName())->toBe('Mission Question');
});

it('defines transcript polymorphic relations across supported models', function () {
    $transcript = new Transcript;
    $missionSession = new MissionSession;
    $missionQuestion = new MissionQuestion;
    $event = new PRFEvent;

    expect($transcript->transcriptable())->toBeInstanceOf(MorphTo::class)
        ->and($transcript->missionSession())->toBeInstanceOf(BelongsTo::class)
        ->and($missionSession->transcripts())->toBeInstanceOf(MorphMany::class)
        ->and($missionQuestion->transcripts())->toBeInstanceOf(MorphMany::class)
        ->and($event->transcripts())->toBeInstanceOf(MorphMany::class);
});
