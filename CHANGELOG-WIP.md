# Release Notes for Craft Commerce (WIP)

### Store Management
- It is now possible to design card views for Products and Variants. ([#3809](https://github.com/craftcms/commerce/pull/3809))
- Order conditions can now have a “Coupon Code” rule. ([#3776](https://github.com/craftcms/commerce/discussions/3776))
- Order conditions can now have a “Payment Gateway” rule. ([#3722](https://github.com/craftcms/commerce/discussions/3722))
- Variant conditions can now have a “Product” rule.
- Tax rates now have statuses. ([#3790](https://github.com/craftcms/commerce/discussions/3790))

### Administration
- The `to`, `bcc`, and `cc` email fields now support environment variables. ([#3738](https://github.com/craftcms/commerce/issues/3738))

### Development
- Added the `couponCode` order query param.
- Added an `originalCart` value to the `commerce/update-cart` failed ajax response. ([#430](https://github.com/craftcms/commerce/issues/430))
- Added a new "Payment Gateway" order condition rule. ([#3722](https://github.com/craftcms/commerce/discussions/3722))

### Extensibility

- Added `craft\commerce\elements\Order::getTeller()`.
- Added `craft\commerce\elements\conditions\variants\ProductConditionRule`.
- Added `craft\commerce\base\InventoryItemTrait`.
- Added `craft\commerce\base\InventoryLocationTrait`.
- Added `craft\commerce\elements\conditions\orders\CouponCodeConditionRule`.
- Added `craft\commerce\elements\conditions\variants\ProductConditionRule`.
- Added `craft\commerce\elements\db\OrderQuery::$couponCode`.
- Added `craft\commerce\elements\db\OrderQuery::couponCode()`.
- Added `craft\commerce\events\CartPurgeEvent`.
- Added `craft\commerce\services\Inventory::updateInventoryLevel()`.
- Added `craft\commerce\services\Inventory::updatePurchasableInventoryLevel()`.
- Added `craft\commerce\services\TaxRates::getAllEnabledTaxRates()`.

### System
- Craft Commerce now requires Craft CMS 5.5 or later.
