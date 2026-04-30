<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassGroup\Resource;
use App\Models\ClassGroup;

class ClassGroupController extends Controller
{
    protected ?string $modelClass = ClassGroup::class;

    protected ?string $resourceClass = Resource::class;

    protected int $defaultLimit = 100;
}
