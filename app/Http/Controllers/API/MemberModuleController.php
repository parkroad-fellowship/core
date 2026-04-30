<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\MemberModule\Resource;
use App\Models\MemberModule;

class MemberModuleController extends Controller
{
    protected ?string $modelClass = MemberModule::class;

    protected ?string $resourceClass = Resource::class;
}
