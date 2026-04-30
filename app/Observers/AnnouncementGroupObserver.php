<?php

namespace App\Observers;

use App\Events\AnnouncementGroup\Created;
use App\Http\Resources\AnnouncementGroup\Resource;
use App\Models\AnnouncementGroup;

class AnnouncementGroupObserver
{
    /**
     * Handle the AnnouncementGroup "created" event.
     */
    public function created(AnnouncementGroup $announcementGroup): void
    {
        $group = $announcementGroup->group;

        Created::dispatch(
            new Resource($announcementGroup),
            $group->ulid,
        );
    }

    /**
     * Handle the AnnouncementGroup "updated" event.
     */
    public function updated(AnnouncementGroup $announcementGroup): void
    {
        //
    }

    /**
     * Handle the AnnouncementGroup "deleted" event.
     */
    public function deleted(AnnouncementGroup $announcementGroup): void
    {
        //
    }

    /**
     * Handle the AnnouncementGroup "restored" event.
     */
    public function restored(AnnouncementGroup $announcementGroup): void
    {
        //
    }

    /**
     * Handle the AnnouncementGroup "force deleted" event.
     */
    public function forceDeleted(AnnouncementGroup $announcementGroup): void
    {
        //
    }
}
