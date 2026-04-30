<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\Announcement\Resource;
use App\Models\Announcement;

class AnnouncementController extends Controller
{
    protected ?string $modelClass = Announcement::class;

    protected ?string $resourceClass = Resource::class;
}
