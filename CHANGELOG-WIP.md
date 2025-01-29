# Release Notes for Craft Commerce (WIP)

### Store Management
- Archived gateways are now listed on the Gateways index page. ([#3839](https://github.com/craftcms/commerce/issues/3839))
- Added card view designers for products and variants. ([#3809](https://github.com/craftcms/commerce/pull/3809))
- Order conditions can now have “Coupon Code” and “Payment Gateway” rules. ([#3776](https://github.com/craftcms/commerce/discussions/3776), [#3722](https://github.com/craftcms/commerce/discussions/3722))
- Product variant conditions can now have a “Product” rule.
- Tax rates now have statuses. ([#3790](https://github.com/craftcms/commerce/discussions/3790))
- It’s now possible to restore soft-deleted product variants.

### Administration
- The “Recipient”, “BCC’d Recipient”, and “CC’d Recipient” email settings now support being set to environment variables. ([#3738](https://github.com/craftcms/commerce/issues/3738))
- It’s now possible to view (but not edit) system and plugin settings on environments where `allowAdminChanges` is disabled. 

### Development
- Added the `couponCode` order query param.
- Orders’ `makePrimaryShippingAddress` and `makePrimaryBillingAddress` property values now persist during checkout.
- The `commerce/update-cart` action now includes an `originalCart` key in JSON responses. ([#430](https://github.com/craftcms/commerce/issues/430))

### Extensibility
- Added support for registering custom tax ID validators.
- Added `craft\commerce\base\InventoryItemTrait`.
- Added `craft\commerce\base\InventoryItemTrait`.
- Added `craft\commerce\base\InventoryLocationTrait`.
- Added `craft\commerce\base\InventoryLocationTrait`.
- Added `craft\commerce\base\Purchasable::hasInventory()`.
- Added `craft\commerce\base\Purchasable::loadSales()`.
- Added `craft\commerce\base\TaxIdValidatorInterface`.
- Added `craft\commerce\controllers\BaseStoreManagementController::getStoreSwitch()`.
- Added `craft\commerce\elements\Purchasable::$allowOutOfStockPurchases`.
- Added `craft\commerce\elements\Purchasable::getIsOutOfStockPurchasingAllowed()`.
- Added `craft\commerce\elements\conditions\orders\CouponCodeConditionRule`.
- Added `craft\commerce\elements\conditions\variants\ProductConditionRule`.
- Added `craft\commerce\elements\db\OrderQuery::$couponCode`.
- Added `craft\commerce\elements\db\OrderQuery::couponCode()`.
- Added `craft\commerce\events\CartPurgeEvent`.
- Added `craft\commerce\events\PurchasableOutOfStockPurchasesAllowedEvent`.
- Added `craft\commerce\services\Gateways\getAllArchivedGateways()`.
- Added `craft\commerce\services\Inventory::updateInventoryLevel()`.
- Added `craft\commerce\services\Inventory::updateInventoryLevel()`.
- Added `craft\commerce\services\Inventory::updatePurchasableInventoryLevel()`.
- Added `craft\commerce\services\Inventory::updatePurchasableInventoryLevel()`.
- Added `craft\commerce\services\Purchasables::EVENT_PURCHASABLE_OUT_OF_STOCK_PURCHASES_ALLOWED`.
- Added `craft\commerce\services\Purchasables::isPurchasableOutOfStockPurchasingAllowed()`.
- Added `craft\commerce\services\Taxes::EVENT_REGISTER_TAX_ID_VALIDATORS`.
- Added `craft\commerce\services\Taxes::getEnabledTaxIdValidators()`.
- Added `craft\commerce\services\Taxes::getTaxIdValidators()`.
- Added `craft\commerce\taxidvalidators\EuVatIdValidator`.

### System
- Craft Commerce now requires Craft CMS 5.6.0 or later.
- Fixed a bug where orders’ promotional prices could be calculated incorrectly when using sales.
- Fixed a bug where the `commerce/cart/update-cart` action wasn’t respecting `makePrimaryShippingAddress` and `makePrimaryBillingAddress` params for newly-created addresses. ([#3864](https://github.com/craftcms/commerce/pull/3864))
- Fixed a PHP error that could occur when viewing discounts. ([#3844](https://github.com/craftcms/commerce/issues/3844))
