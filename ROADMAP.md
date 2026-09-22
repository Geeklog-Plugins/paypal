# PayPal plugin roadmap

This roadmap follows the shared modernization guidance maintained in
[Geeklog-Plugins/memorandum](https://github.com/Geeklog-Plugins/memorandum).

The current transition target is Geeklog 2.1.1 through 2.2.2 and PHP 5.6
through PHP 8.1. The goal of 1.7.x is to stabilize the existing plugin without
silently changing payment semantics.

## 1.7.0 — stabilization and interoperability

### Runtime and upgrade safety

- [x] Raise the maintained Geeklog baseline to 2.1.1.
- [x] Declare PHP 5.6 as the minimum maintained PHP version.
- [x] Fix the PHP 7/8 fatal control-flow error reported in issue #3.
- [x] Replace the removed `preg_replace(... /e)` execution path used by legacy
      serialized IPN records.
- [x] Fix known PHP 8 jCart count expressions and undefined cart-entry access.
- [x] Remove install/upgrade telemetry mail.
- [x] Stop upgrade code from renaming or deleting shared public plugin files.
- [x] Resolve Geeklog core groups by name instead of hard-coded group IDs.
- [x] Make download-log creation non-fatal.
- [ ] Complete the remaining PHP 8 warning audit for optional request keys and
      old admin code paths.
- [ ] Add an automated release-candidate smoke test for install, enable,
      disable, uninstall and 1.6.2 -> 1.7.0 upgrade.

### Security

- [x] Add CSRF protection to product save/delete.
- [x] Replace pending-order validation GET mutation with CSRF-protected POST.
- [ ] Add CSRF protection to the remaining legacy administration AJAX and CRUD
      mutation paths.
- [ ] Normalize remaining SQL construction around `DB_escapeString()` and
      integer casts.
- [ ] Review IPN log retention and administrator-visible personally identifiable
      information.
- [ ] Extend IP address storage for IPv6 without losing historical records.

### Agent, Eclipse and Hub interoperability

- [x] Add static `plugin.json` metadata.
- [x] Add `plugin_getcapabilities_paypal()`.
- [x] Expose permission-aware product Item Info.
- [x] Expose product collections through `id='*'` with limit/since/order.
- [x] Expose normalized per-product `hits` and `hits-desc` collections.
- [x] Add product ID-to-URL resolution.
- [x] Emit product save/delete lifecycle events.
- [x] Add the read-only, admin-authorized `dashboard_summary` service used by
      Eclipse.
- [ ] Add URL-to-ID resolution if a consumer demonstrates a concrete need.
- [ ] Add a bounded sitemap/feed surface if PayPal products are intended to be
      indexed as first-class site content.

Only catalog/product information is exposed through the shared content
contract. Purchases, IPN payloads, customer details and payment credentials are
not exposed as Agent/Hub content resources.

### Configuration and administration

- [ ] Modernize the legacy configuration hierarchy with explicit symbolic tabs
      while preserving existing `conf_values` data.
- [ ] Move substantially modernized administration pages to
      `COM_createHTMLDocument()` with a Geeklog 2.1.1-compatible fallback where
      required.
- [ ] Replace remaining inline administration JavaScript with bounded asset
      files where practical.
- [ ] Move user-facing/admin strings that are still hard-coded into language
      files.

## 1.7.x — focused follow-up improvements

- [ ] Issue #1: implement a documented `[paypal:count]` autotag for the current
      cart item count after the session/cart contract is covered by tests.
- [ ] Issue #2: define the intended category/cart-list behavior and acceptance
      criteria before implementation.
- [ ] Replace TimThumb-based thumbnail rendering with Geeklog/native image
      handling and remove TimThumb from the distribution.
- [ ] Replace the legacy jqPlot purchase-history chart.
- [ ] Reduce direct `$_GET`, `$_POST` and `$_REQUEST` access in jCart and
      administration code.
- [ ] Add tests for permissions, hidden/inactive products, Item Info collections
      and dashboard summaries.

## 1.8 — payment and storage modernization

These items intentionally remain outside the 1.7.0 stabilization scope because
they can change payment/storage behavior.

- [ ] Introduce a modern PayPal Checkout/REST integration alongside the legacy
      IPN path before any migration.
- [ ] Add verified webhook handling with replay/idempotency protection.
- [ ] Define a migration/deprecation path for legacy IPN processing.
- [ ] Review MyISAM tables and plan a non-destructive InnoDB migration.
- [ ] Add explicit charset/collation migration where needed.
- [ ] Modernize the cart implementation and remove the bundled legacy jCart
      dependency.
- [ ] Separate catalog, order, payment and fulfillment services internally while
      keeping stable Geeklog-facing contracts.

## Issue tracking

The public issue tracker remains authoritative for individual bugs and feature
requests:

- #1 — cart count autotag: targeted for 1.7.x after cart/session stabilization.
- #2 — category/cart listing behavior: specification required before coding.
- #3 — PHP fatal `break` outside loop/switch: fixed in the 1.7.0 stabilization
  branch.

## Integrated extended features

- Integrated the extended feature set directly into the standard plugin: product attributes, attribute types, manual subscriptions, expiration notifications, recurring-payment NVP helpers, and sales statistics.
The `PAYPALPRO_*` function names are retained only as an internal compatibility contract; no separate Pro package is required.
