<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Course\Resource;
use App\Models\Course;

class CourseController extends Controller
{
    protected ?string $modelClass = Course::class;

    protected ?string $resourceClass = Resource::class;
}
