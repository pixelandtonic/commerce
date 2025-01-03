# Release Notes for Craft Commerce (WIP)

### Fixed

- Fixed a PHP error that could occur when calculating tax totals. ([#3822](https://github.com/craftcms/commerce/issues/3822))

### Store Management
- Order conditions can now have a “Coupon Code” rule. ([#3776](https://github.com/craftcms/commerce/discussions/3776))
- Order conditions can now have a “Payment Gateway” rule. ([#3722](https://github.com/craftcms/commerce/discussions/3722))
- Variant conditions can now have a “Product” rule.

### Administration
- Added support for `to`, `bcc`, and `cc` email fields to support environment variables. ([#3738](https://github.com/craftcms/commerce/issues/3738))

### Development
- Added the `couponCode` order query param.
- Added an `originalCart` value to the `commerce/update-cart` failed ajax response. ([#430](https://github.com/craftcms/commerce/issues/430))

### Extensibility

- Added `craft\commerce\elements\Order::getTeller()`.
- Added `craft\commerce\elements\conditions\variants\ProductConditionRule`.
- Added `craft\commerce\base\InventoryItemTrait`.
- Added `craft\commerce\base\InventoryLocationTrait`.
- Added `craft\commerce\elements\conditions\orders\CouponCodeConditionRule`.
- Added `craft\commerce\elements\conditions\variants\ProductConditionRule`.
- Added `craft\commerce\elements\db\OrderQuery::$couponCode`.
- Added `craft\commerce\elements\db\OrderQuery::couponCode()`.
- Added `craft\commerce\services\Inventory::updateInventoryLevel()`.
- Added `craft\commerce\services\Inventory::updatePurchasableInventoryLevel()`.