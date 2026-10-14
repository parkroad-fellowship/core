# Notifications

## Naming

`app/Notifications/{Model}/{Model}{Event}Notification.php`. The folder is the model the constructor takes, and the class name repeats it so imports never collide:

- `Mission/MissionApprovedNotification`
- `Requisition/RequisitionRejectedNotification`
- `AccountingEvent/AccountingEventCreatedNotification`
- `Pledge/PledgeDueNotification`

## Shape

Every notification extends `App\Notifications\BaseNotification`:

```php
class MissionApprovedNotification extends BaseNotification
{
    public function __construct(public Mission $mission) {}

    public function type(): PRFNotificationType
    {
        return PRFNotificationType::MISSION_APPROVED;
    }

    public function targetApp(object $notifiable): PRFAppTopics
    {
        return PRFAppTopics::MISSIONS_APP;
    }

    public function toMail(object $notifiable): MailMessage { /* … */ }

    protected function fcmTitle(): string { /* … */ }

    protected function fcmBody(): string { /* … */ }
}
```

`BaseNotification` handles the rest:
- it is queued on `high`;
- `via()` sends mail, plus FCM when the tenant has FCM configured and the notifiable has tokens;
- the FCM payload uses `type()->value`.

Don't add empty `toArray()` stubs.

## Recipients

- **Members and users:** `Notification::send($members, new …)`. Mail goes to the address from `Member::routeNotificationForMail()`, which depends on the tenant's email mode.
- **Desk mailboxes:** `Notification::route('mail', Utils::getDeskEmails(PRFResponsibleDesk::MISSIONS))->notify(new …)`. Read desk emails through `Utils::getDeskEmails()` only.
- **A raw email address** (for example a pledger): `Notification::route('mail', $pledge->email)`. Never pass a string to `Notification::send`.

## Where they are sent from

Notifications are sent from **queued listeners** reacting to domain events (see Jobs & Side Effects). Never send them from observers, controllers or Filament actions.

## Secrets

Never put a password or token in a queued notification's constructor, because it would be stored in the `jobs` and `failed_jobs` tables. If you must send one, call `Notification::sendNow()` from inside the job that generated it.
