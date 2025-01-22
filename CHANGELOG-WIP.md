# Release Notes for Craft Commerce 4.8 (WIP)

- It is now possible to register custom tax ID validators.
- Added `craft\commerce\base\TaxIdValidatorInterface`.
- Added `craft\commerce\taxidvalidators\EuVatIdValidator`.
- Added `craft\commerce\services\Taxes::EVENT_REGISTER_TAX_ID_VALIDATORS`.
- Added `\craft\commerce\services\Taxes::getTaxIdValidators()`.
- Added `\craft\commerce\services\Taxes::getEnabledTaxIdValidators()`.