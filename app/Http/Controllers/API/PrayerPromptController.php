<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\PrayerPrompt\Resource;
use App\Models\PrayerPrompt;

class PrayerPromptController extends Controller
{
    protected ?string $modelClass = PrayerPrompt::class;

    protected ?string $resourceClass = Resource::class;
}
