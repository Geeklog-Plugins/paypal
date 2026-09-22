# PayPal 1.7.0 release notes

- Integrated the former Pro-version feature set into the standard plugin: product attributes, attribute types, manual subscriptions, expiration notifications, recurring-payment NVP helpers, and sales statistics.

PayPal 1.7.0 is a stabilization and interoperability release for the Geeklog
PayPal plugin.

The release focuses on making the existing catalog and payment integration safer
to operate on the current Geeklog transition baseline while preserving the
legacy payment flow.

## Highlights

- Geeklog 2.1.1+ / PHP 5.6+ maintained baseline.
- Fix for the PHP 7/8 fatal error tracked as issue #3.
- Removal of legacy `preg_replace /e` execution from the touched transaction
  handling paths.
- Safer upgrades for shared-file/multisite installations.
- No install/upgrade telemetry email.
- CSRF protection for product mutations and manual pending-order validation.
- Product interoperability for Agent and Hub through normalized Item Info,
  collections, URL resolution and lifecycle events.
- Generic Eclipse administration integration through
  `dashboard.summary`.

## Interoperability contract

PayPal 1.7.0 exposes its public, permission-aware product catalog as content.
Consumers do not need to query PayPal tables directly.

Advertised capabilities:

- `content.read`
- `content.collection`
- `content.search`
- `content.popular`
- `content.url.resolve`
- `content.lifecycle`
- `dashboard.summary`

The dashboard service is restricted to users with `paypal.admin`.

Payment transactions, IPN payloads, customer records and payment credentials are
intentionally not exposed through the generic content contract.

## Upgrade behavior

The database plugin version is updated normally, but 1.7.0 no longer renames or
deletes the plugin's shared public directory during an individual site upgrade.
This is important for installations where several Geeklog sites share one plugin
codebase.

## Known modernization work

The legacy plugin still contains areas that should be modernized in follow-up
work, notably remaining admin AJAX/CRUD CSRF coverage, PHP 8 warning cleanup,
TimThumb, jqPlot, jCart, MyISAM storage and the legacy PayPal IPN integration.
These items are tracked in `ROADMAP.md`.

Issue #1 (cart-count autotag) is planned as a focused 1.7.x improvement after
cart/session tests are in place. Issue #2 needs clearer behavior and acceptance
criteria before implementation.
