# Release Notes for Craft Commerce (WIP)

### Administration
- Added a new "Coupon Code" order condition rule. ([#3776](https://github.com/craftcms/commerce/discussions/3776))
- Added a new "Payment Gateway" order condition rule. ([#3722](https://github.com/craftcms/commerce/discussions/3722))

### Development
- Added the `couponCode` order query param.

### Extensibility
- Added `craft\commerce\elements\conditions\orders\CouponCodeConditionRule`.
- Added `craft\commerce\elements\db\OrderQuery::$couponCode`.
- Added `craft\commerce\elements\db\OrderQuery::couponCode()`.