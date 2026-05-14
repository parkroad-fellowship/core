<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Transcript\Resource;
use App\Models\Transcript;

class TranscriptController extends Controller
{
    protected ?string $modelClass = Transcript::class;

    protected ?string $resourceClass = Resource::class;
}
