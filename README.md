# PayPal for Geeklog

PayPal is a Geeklog shopping/catalog plugin with products, subscriptions,
downloads, stock, shipping and legacy PayPal IPN payment handling.

## PayPal 1.7.0 development baseline

The 1.7.0 stabilization branch targets:

- Geeklog 2.1.1 through 2.2.2
- PHP 5.6 through PHP 8.1

The release preserves the existing payment model while modernizing runtime
compatibility, upgrade safety, security and Geeklog ecosystem interoperability.

## Interoperability

PayPal exposes its permission-aware public product catalog through shared
Geeklog contracts so consumers do not need to query plugin tables directly.

Supported provider capabilities include:

- `content.read`
- `content.collection`
- `content.search`
- `content.popular`
- `content.url.resolve`
- `content.lifecycle`
- `dashboard.summary`

Agent and Hub can consume normalized product Item Info and lifecycle events.
Eclipse can consume the admin-authorized `dashboard_summary` service.

Purchases, customer details, IPN payloads and payment credentials are not
exposed as generic content resources.

## Documentation

- [Release notes](RELEASE_NOTES.md)
- [Changelog](CHANGELOG.md)
- [Roadmap](ROADMAP.md)
- [Issues and feature requests](https://github.com/Geeklog-Plugins/paypal/issues)

The shared modernization conventions used by this work are maintained in the
[Geeklog Plugins Memorandum](https://github.com/Geeklog-Plugins/memorandum).

## Contributing

1. Fork the repository.
2. Create a feature branch.
3. Commit and test the change.
4. Push the branch.
5. Open a pull request.

## Integrated extended features

PayPal 1.7.0 includes the functionality that was historically distributed as a separate Pro version. Product attributes, attribute types, manual subscriptions, subscription expiration notifications, recurring-payment helpers, and sales statistics are bundled with the plugin. No separate Pro package is required.
