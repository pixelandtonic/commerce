# Release Notes for Craft Commerce 4.8 (WIP)

### Store Management
- Archived gateways are now listed on the Gateways index page. ([#3839](https://github.com/craftcms/commerce/issues/3839))

### Extensibility
- Added support for registering custom tax ID validators.
- Added `\craft\commerce\services\Taxes::getEnabledTaxIdValidators()`.
- Added `\craft\commerce\services\Taxes::getTaxIdValidators()`.
- Added `craft\commerce\base\TaxIdValidatorInterface`.
- Added `craft\commerce\services\Gateways\getAllArchivedGateways()`.
- Added `craft\commerce\services\Taxes::EVENT_REGISTER_TAX_ID_VALIDATORS`.
- Added `craft\commerce\taxidvalidators\EuVatIdValidator`.
