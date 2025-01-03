# Release Notes for Craft Commerce (WIP)

### Store Management
- It is now possible to design card views for Products and Variants. ([#3809](https://github.com/craftcms/commerce/pull/3809))
- Order conditions can now have a “Coupon Code” rule. ([#3776](https://github.com/craftcms/commerce/discussions/3776))
- Order conditions can now have a “Payment Gateway” rule. ([#3722](https://github.com/craftcms/commerce/discussions/3722))
- Variant conditions can now have a “Product” rule.
- Tax rates now have statuses. ([#3790](https://github.com/craftcms/commerce/discussions/3790))
- Soft-deleted variants can now be restored.

### Administration
- Added support for environment variables to the `to`, `bcc`, and `cc` email fields. ([#3738](https://github.com/craftcms/commerce/issues/3738))

### Development
- Added the `couponCode` order query param.
- Added an `originalCart` value to `commerce/update-cart` action, for failed ajax responses. ([#430](https://github.com/craftcms/commerce/issues/430))

### Extensibility
- Added `craft\commerce\base\InventoryItemTrait`.
- Added `craft\commerce\base\InventoryLocationTrait`.
- Added `craft\commerce\elements\Purchasable::$allowOutOfStockPurchases`.
- Added `craft\commerce\elements\Purchasable::getIsOutOfStockPurchasesAllowed()`.
- Added `craft\commerce\elements\conditions\orders\CouponCodeConditionRule`.
- Added `craft\commerce\elements\conditions\variants\ProductConditionRule`.
- Added `craft\commerce\elements\db\OrderQuery::$couponCode`.
- Added `craft\commerce\elements\db\OrderQuery::couponCode()`.
- Added `craft\commerce\events\CartPurgeEvent`.
- Added `craft\commerce\events\PurchasableAllowOutOfStockPurchasesEvent`.
- Added `craft\commerce\services\Inventory::updateInventoryLevel()`.
- Added `craft\commerce\services\Inventory::updatePurchasableInventoryLevel()`.
- Added `craft\commerce\services\Purchasables::EVENT_PURCHASABLE_ALLOW_OUT_OF_STOCK_PURCHASES`.
- Added `craft\commerce\services\Purchasables::isOutOfStockPurchasesAllowed()`.

### System
- Craft Commerce now requires Craft CMS 5.5 or later.
