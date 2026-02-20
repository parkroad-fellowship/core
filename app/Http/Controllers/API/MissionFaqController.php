<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MissionFaq\Resource;
use App\Models\MissionFaq;

class MissionFaqController extends Controller
{
    protected ?string $modelClass = MissionFaq::class;

    protected ?string $resourceClass = Resource::class;
}
