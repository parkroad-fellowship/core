# Mago Formatter and PHPStan

This project formats PHP with **Mago**. Laravel Pint is not installed. Don't run `vendor/bin/pint`.

- After changing PHP files, run `vendor/bin/mago fmt` before you finish.
- To check formatting without changing files, run `vendor/bin/mago fmt --check`. The Check Formatting CI workflow enforces this.
- Static analysis: `make stan` (PHPStan + Larastan, **level 10**, no baseline). New code must pass with no ignores. The custom rule `NoQueryBuilderWritesRule` rejects query-level writes in jobs, listeners and actions.
