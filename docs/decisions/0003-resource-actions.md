# 0003 Resource actions

- Status: accepted
- Date: 2026-10-27

## Context and problem statement

Business changes from the Lab console must run the app's own code, never write columns directly, and must not overwrite a record someone else changed after the operator read it (LAB-029). Some actions need the operator's confirmation and recent two-factor, which only the Lab can check.

## Considered options

1. Edit declared fields directly, with a version check.
2. Named actions on a resource that call an app class, with a version check under a row lock.

## Decision outcome

Option 2:

- A resource declares `actions()`: `Action::make('refund', RefundOrder::class)->confirm()->requiresMfa()`. The class lives in the app and implements `Overclock\LabKit\Contracts\ResourceAction`, whose `handle(Model $record, Operator $operator): array` calls the app's Actions and returns a small result.
- `lab:resources:action` takes `resource`, `key`, `action`, `version`, `confirmed` and `mfa`. It refuses with `confirmation_required` or `mfa_required` when the action declares them and the Lab did not confirm; `requiresMfa()` implies confirmation. The Lab enforces recent two-factor on its side; the flag records that it did.
- Inside one transaction on the model's connection, the record is read with `lockForUpdate`, the model Policy method `labRun(Operator $operator, Model $record, string $action)` decides (refused without it), and the record's version must equal `version`, otherwise `conflict`. The version is an HMAC-SHA256, keyed with the app key, of the model class and every attribute as stored, so any change is detected, even within the same second and on models without `updated_at`; it reveals nothing about the values. The action runs inside the same transaction, so a failure rolls back what it changed and answers `internal_error` without the message.
- Contract for the app: an action must record what it did on the record itself (a status, a `refunded_at`). If the record is unchanged afterwards, the call answers `unchanged` and is rolled back, because a repeated call with the same version could not be told apart.
- Listings return `_key` and `_version` with each row, so the Lab sends back the version it showed.

Option 1 was rejected because the technical design keeps business changes in the app's code.

### Consequences

- Good: two operators acting on the same record cannot both win; the second gets `conflict` and reloads.
- Bad: an action that only has effects outside the record, such as calling a payment provider, must still mark the record; the package refuses it otherwise.
