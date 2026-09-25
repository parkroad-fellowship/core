# System Access

Seeded accounts and passwords for non-production environments. The full list, with what each account is for, is in [developer-invite.md](./developer-invite.md#seeded-accounts).

## Passwords

| Environment | Password |
|---|---|
| Local (`APP_ENV=local`) | `1password` |
| Hosted demo, staging and development | `asZDcVt7Q` |
| Production | Random per seed, never documented; email/password sign-in is disabled |

`php artisan migrate:fresh --seed` prints the password it used.

## Environments

- Demo (pre-configured): [https://demo.parkroadfellowship.org](https://demo.parkroadfellowship.org)
- Development (pull requests deploy here): [https://dev-app.parkroadfellowship.org](https://dev-app.parkroadfellowship.org)
- Fellowship admin panel: `admin@example.org`
- Central panel (all fellowships): `engineering@parkroadfellowship.org`

## Notes

- Seeded addresses use `example.org`, which is reserved for documentation and never reaches a real inbox.
- These are shared, non-production credentials: never reuse them, and rotate them periodically.
- Integration credentials (Paystack, SMS, Firebase, AI, Google Workspace) are entered per fellowship in App Settings and are never shared between environments.
