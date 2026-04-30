<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\LessonModule\Resource;
use App\Models\LessonModule;

class LessonModuleController extends Controller
{
    protected ?string $modelClass = LessonModule::class;

    protected ?string $resourceClass = Resource::class;
}
