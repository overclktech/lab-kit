# 0002 Read-only data resources

- Status: accepted
- Date: 2026-10-27

## Context and problem statement

The Lab console shows the data each project declares (LAB-028), in the style of Filament resources, without an administration route in the app and without bypassing its rules. The operator is a Lab member, not a user of the app, and some columns must never leave the app.

## Considered options

1. Authorize through `App\LabKit\UserActions::allows`, as the user commands do.
2. Authorize through the model Policy, with a method that receives the Lab operator.
3. Let each resource decide alone.

## Decision outcome

Option 2:

- Resources are classes `app/LabKit/*Resource.php` extending `Overclock\LabKit\Resources\LabResource`, found by file name, keyed by the kebab name without `Resource` (`OrderResource` is `order`).
- Reading asks the model Policy method `labViewAny(Operator $operator)`. A Policy without that method, or a model without a Policy, is refused, so access is closed by default and the rule lives next to the model's other rules.
- `lab:resources` returns only the resources the operator may view, with each field's name, type and whether it filters. `lab:resources:list` returns only declared fields, newest first, with exact-match filters limited to fields marked `filterable()` and the page size the resource declares.
- A field whose name looks sensitive (password, token, secret, remember, two factor, recovery, private or API key), or that the model hides with `$hidden`, cannot be declared.

Option 1 was rejected because it puts every model's rule in one class far from the model. Option 3 was rejected because the decision would not go through the Policy, as the technical design requires.

### Consequences

- Good: the app's Policy decides; nothing is exposed until the app writes `labViewAny`.
- Bad: no per-record rule in listings yet; a resource that must hide some rows narrows them by overriding `query()`.
