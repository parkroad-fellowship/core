<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MissionSessionTranscript\Resource;
use App\Models\MissionSessionTranscript;

class MissionSessionTranscriptController extends Controller
{
    protected ?string $modelClass = MissionSessionTranscript::class;

    protected ?string $resourceClass = Resource::class;
}
