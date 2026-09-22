# Changelog

- Integrated the extended feature set directly into the standard plugin: product attributes, attribute types, manual subscriptions, expiration notifications, recurring-payment NVP helpers, and sales statistics.

## 1.7.0 — in development

### Compatibility and stability

- Set the maintained compatibility baseline to Geeklog 2.1.1+ and PHP 5.6+.
- Fixed the PHP 7/8 fatal error caused by `break` outside a loop/switch in the
  manual purchase validation path (Geeklog-Plugins/paypal issue #3).
- Replaced legacy `preg_replace()` expressions using the removed `/e`
  modifier in touched purchase/upgrade paths.
- Fixed jCart item-count expressions that are invalid or unsafe on modern PHP.
- Initialized plugin header output deterministically.
- Added safer global binding for Geeklog 2.2.x plugin loading.

### Install and upgrade safety

- Removed install and upgrade telemetry email.
- Removed automatic public-directory rename/delete operations from the upgrade
  path so shared plugin files are not mutated by one site's database upgrade.
- Resolve Logged-in Users and All Users by group name instead of hard-coded
  numeric IDs.
- Download-log creation no longer aborts installation if the log cannot be
  created.
- Updated static plugin metadata for discovery by Monitor and ecosystem tools.

### Security

- Added Geeklog CSRF tokens to product save/delete operations.
- Replaced manual pending-order validation by a CSRF-protected POST operation.
- Escaped the transaction identifier used by the touched manual validation SQL.

### Interoperability

- Added a provider-owned capability declaration.
- Added permission-aware normalized product Item Info.
- Added product collection retrieval with `since`, `limit` and ordering,
  including `hits-desc`.
- Added product ID-to-URL resolution.
- Added product lifecycle notifications using `PLG_itemSaved()` and
  `PLG_itemDeleted()`.
- Added the bounded, read-only `dashboard_summary` service for Eclipse.
- Kept purchases, IPN payloads, customer details and credentials outside the
  generic Agent/Hub content surface.

### Documentation

- Added a modernization roadmap covering remaining security, PHP 8,
  administration, cart, image and payment modernization work.
- Documented the status of upstream issues #1, #2 and #3.

### Upgrade note

Version 1.7.0 is designed as a stabilization release. It does not migrate the
payment model to PayPal REST/Checkout and does not intentionally change the
existing IPN payment semantics.
