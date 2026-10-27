# 0001 Artisan bridge contract

- Status: accepted
- Date: 2026-10-27

## Context and problem statement

The Lab manages users and sessions inside each client app through the executor action `console.call`, which runs `php artisan lab:<command>` in the app container. Every command needs the same input and output shape, and the rules for blocking a user or forcing a password reset belong to the app, not to the package. Removing the package and `app/LabKit/` must not break the app.

## Considered options

1. Commands read options and arguments from the command line.
2. Commands read one JSON document from standard input and answer one JSON document on standard output.
3. The app exposes HTTP routes for the Lab.

## Decision outcome

Option 2, with these rules:

- Input: `{"operation_id": "...", "operator": {"id": "...", "name": "..."}, "params": {...}}`, at most 64 KiB. Unknown keys are refused at every level; each command validates its own `params`.
- Output: `{"ok": true, "data": {...}}` with exit code 0, or `{"ok": false, "error": {"code": "...", "message": "..."}}` with exit code 1. Codes: `invalid_json`, `input_too_large`, `invalid_input`, `not_found`, `forbidden`, `unsupported`.
- Listing users and sessions is generic: users come from the Eloquent user provider `users`, sessions from the `database` session table.
- Session IDs never leave the app: each session is identified by the SHA-256 of its ID (`handle`). Revoking all sessions also rotates the remember token.
- Every user and session command goes through `App\LabKit\UserActions`, a class in the app that implements `Overclock\LabKit\Contracts\UserActions`. Reading users, reading sessions and revoking sessions ask its `allows` method first, which applies the app's Policies; blocking, unblocking and forcing a password reset call the app's own Actions. A denial, or an `AuthorizationException` from the app, answers `forbidden` without data. The package discovers the class by name; without it, only `lab:capabilities` is registered, so access is refused by default.

Option 1 was rejected because arguments show up in process listings and the executor sends parameters over standard input. Option 3 was rejected because the app would expose an administration route to the internet.

### Consequences

- Good: one parser, one validator and one response shape for every `lab:*` command.
- Good: `app/LabKit/` is the only place that depends on the package, so `composer remove` plus deleting that folder leaves a plain Laravel app.
- Bad: apps with a user provider other than Eloquent `users`, or sessions outside the `database` driver, are not supported.
