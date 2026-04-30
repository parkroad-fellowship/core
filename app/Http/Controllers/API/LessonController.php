<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Lesson\Resource;
use App\Models\Lesson;

class LessonController extends Controller
{
    protected ?string $modelClass = Lesson::class;

    protected ?string $resourceClass = Resource::class;
}
