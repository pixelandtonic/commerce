# Release Notes for Craft Commerce 4.8 (WIP)

### Store Management
- It is now possible to see archived gateways listed in the control panel. ([#3839](https://github.com/craftcms/commerce/issues/3839))

### Extensibility
- It is now possible to register custom tax ID validators.
- Added `craft\commerce\services\Gateways\getAllArchivedGateways()`.
- Added `craft\commerce\base\TaxIdValidatorInterface`.
- Added `craft\commerce\taxidvalidators\EuVatIdValidator`.
- Added `craft\commerce\services\Taxes::EVENT_REGISTER_TAX_ID_VALIDATORS`.
- Added `\craft\commerce\services\Taxes::getTaxIdValidators()`.
- Added `\craft\commerce\services\Taxes::getEnabledTaxIdValidators()`.
