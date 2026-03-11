<?php

namespace App\Observers;

use App\Enums\PRFApprovalStatus;
use App\Enums\PRFResponsibleDesk;
use App\Helpers\Utils;
use App\Models\Member;
use App\Models\Requisition;
use App\Notifications\Requisition\RecallNotification;
use Illuminate\Support\Facades\Notification;

class RequisitionObserver
{
    /**
     * Handle the Requisition "created" event.
     */
    public function created(Requisition $requisition): void
    {
        //
    }

    /**
     * Handle the Requisition "updated" event.
     */
    public function updated(Requisition $requisition): void
    {
        $changed = $requisition->getChanges();

        if (isset($changed['approval_status']) && $changed['approval_status'] === PRFApprovalStatus::RECALLED->value) {
            // Notify initially tagged people about the recall
            $notifiables = Member::query()
                ->whereIn('id', collect([
                    $requisition->member_id,
                    $requisition->appointed_approver_id,
                    $requisition->approved_by,
                ])->filter()->unique()->toArray())
                ->orWhereIn('email', collect([
                    ...Utils::getDeskEmails(PRFResponsibleDesk::from($requisition->responsible_desk)),
                    ...Utils::getDeskEmails(PRFResponsibleDesk::TREASURER_DESK),
                ])->filter()->unique()->toArray())
                ->get();

            Notification::send(
                $notifiables->unique('id'),
                new RecallNotification(requisition: $requisition)
            );
        }
    }

    /**
     * Handle the Requisition "deleted" event.
     */
    public function deleted(Requisition $requisition): void
    {
        //
    }

    /**
     * Handle the Requisition "restored" event.
     */
    public function restored(Requisition $requisition): void
    {
        //
    }

    /**
     * Handle the Requisition "force deleted" event.
     */
    public function forceDeleted(Requisition $requisition): void
    {
        //
    }
}
