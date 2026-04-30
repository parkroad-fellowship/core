<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Module\Resource;
use App\Models\Module;

class ModuleController extends Controller
{
    protected ?string $modelClass = Module::class;

    protected ?string $resourceClass = Resource::class;
}
