<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseModule\Resource;
use App\Models\CourseModule;

class CourseModuleController extends Controller
{
    protected ?string $modelClass = CourseModule::class;

    protected ?string $resourceClass = Resource::class;
}
