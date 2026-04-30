# Contributing to PRF Core

Thank you for contributing to PRF Core.

## Before You Start

- Read the [Code of Conduct](./CODE_OF_CONDUCT.md).
- Search existing issues before opening a new one.
- For setup support, email `engineering@parkroadfellowship.org`.

## Development Setup

1. Fork the repository.
2. Create a branch from the default branch:
   - `feature/<short-description>` or `fix/<short-description>`
3. Set up the project using either local or Docker instructions in [README](./README.md).

## Making Changes

- Keep pull requests focused on one concern.
- Follow existing coding patterns and naming conventions.
- Add or update tests for behavior changes.
- Run formatting and tests locally before pushing:
  - `vendor/bin/pint --dirty`
  - `php artisan test --compact`

## Commit Guidance

- We follow rules 3 & 5 of the manual at: https://cbea.ms/git-commit/
  - Use clear commit messages in imperative style.
  - Capitalize the subject line
- Example: `Add validation for mission approval payload`

## Pull Request Checklist

- Explain what changed and why.
- Link related issue(s) using `Closes #123` when applicable.
- Confirm tests pass locally.
- Include migration notes or environment variable changes if applicable.

## Review Expectations

- Maintainers may request changes before merge.
- Keep discussion focused on behavior, correctness and maintainability.
- Be respectful and constructive in all review conversations.
