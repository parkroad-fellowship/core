<?php

namespace App\Enums;

/**
 * The `type` sent in FCM push payloads, used by the mobile apps to route a tap.
 *
 * Case names follow the notification classes; the values are the strings the shipped apps
 * already understand. Change a value only together with an app release.
 */
enum PRFNotificationType: string
{
    case MISSION_APPROVED = 'new_mission';
    case MISSION_CANCELLED = 'cancelled_mission';
    case MISSION_POSTPONED = 'postponed_mission';
    case MISSION_SERVICED = 'mission_thank_you';
    case MISSION_WHATSAPP_GROUP_LINKED = 'mission_whatsapp_group_created';
    case MISSION_SUBSCRIPTION_STATUS_CHANGED = 'mission_subscription';
    case ACCOUNTING_EVENT_CREATED = 'new_requisition';
    case REQUISITION_APPROVED = 'requisition_approved';
    case REQUISITION_REJECTED = 'requisition_rejected';
    case REQUISITION_RECALLED = 'requisition_recalled';
    case REQUISITION_REVIEW_REQUESTED = 'requisition_review_requested';
    case PRF_EVENT_ANNOUNCED = 'new_event';
    case EVENT_SUBSCRIPTION_CREATED = 'new_event_subscription';
    case STUDENT_ENQUIRY_REPLY_CREATED = 'student_enquiry_reply';
}
