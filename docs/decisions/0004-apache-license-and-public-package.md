# 0004 Apache license and public package

- Status: accepted
- Date: 2026-10-28

## Context and problem statement

The Lab Kit is installed in every client app. A private package needs a token in the app's build, which breaks the client's exit: they leave with a plain Laravel project and our access is revoked. The kit is only a bridge of `lab:*` Artisan commands with no secrets; the product's value is in the Lab, which stays private.

## Considered options

1. Proprietary package in a private repository, installed with a token.
2. MIT, public on Packagist.
3. Apache-2.0, public on Packagist.
4. PolyForm Shield 1.0.0 (source available, forbids competing use).

## Decision outcome

Option 3. The package and its repository are public and installed from Packagist without credentials.

- Clients keep using and changing the kit after they leave, with no contract or token.
- Apache-2.0 adds an explicit patent grant and does not grant use of the Overclock name or trademarks (section 6), which MIT does not address.
- It is an OSI license that client legal teams accept, unlike PolyForm Shield.

Option 1 was rejected because it breaks the zip exit. Option 4 was rejected because some clients' legal teams refuse non-OSI licenses.

### Consequences

- Good: `composer require overclock/lab-kit` works in any build; the client's exit needs nothing from us.
- Bad: anyone can read and reuse the kit, including competitors. Protection relies on keeping the Lab private and on the trademark.
