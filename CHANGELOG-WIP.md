# Release Notes for Craft Commerce (WIP)

### Store Management

- Added support for `to`, `bcc`, and `cc` email fields to support environment variables. ([#3738](https://github.com/craftcms/commerce/issues/3738))
- Added an `originalCart` value to the `commerce/update-cart` failed ajax response. ([#430](https://github.com/craftcms/commerce/issues/430))
- Order conditions can now have a “Payment Gateway” rule. ([#3722](https://github.com/craftcms/commerce/discussions/3722))
- Variant conditions can now have a “Product” rule.

### Extensibility

- Added `craft\commerce\elements\conditions\variants\ProductConditionRule`.