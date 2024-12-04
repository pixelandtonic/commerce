<?php

namespace craft\commerce\migrations;

use craft\commerce\db\Table;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\App;

/**
 * m241204_091901_fix_store_environment_variables migration.
 */
class m241204_091901_fix_store_environment_variables extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Get all the stores current data
        $stores = (new Query())
            ->from(Table::STORES)
            ->all();

        // Get the store settings for each store from the project config
        $storeSettings = \Craft::$app->getProjectConfig()->get('commerce.stores');


        // Store properties to update
        $storeProperties = [
            'autoSetNewCartAddresses',
            'autoSetCartShippingMethodOption',
            'autoSetPaymentSource',
            'allowEmptyCartOnCheckout',
            'allowCheckoutWithoutPayment',
            'allowPartialPaymentOnCheckout',
            'requireShippingAddressAtCheckout',
            'requireBillingAddressAtCheckout',
            'requireShippingMethodSelectionAtCheckout',
            'useBillingAddressForTax',
            'validateOrganizationTaxIdAsVatId',
        ];

        // Update stores env var DB columns
        foreach ($storeProperties as $storeProperty) {
            $this->alterColumn(Table::STORES, $storeProperty, $this->string()->notNull()->defaultValue('false'));
        }

        // Loop through each store and update values in the DB to match the PC values
        foreach ($stores as $store) {
            $storeSettingsForStore = $storeSettings[$store['uid']] ?? null;

            // If there isn't data in the PC for this store, skip it
            if (!$storeSettingsForStore) {
                continue;
            }

            $updateData = [];
            foreach ($storeProperties as $storeProperty) {
                // If there isn't data in the PC for this store property, skip it
                if (!isset($storeSettingsForStore[$storeProperty])) {
                    continue;
                }

                // If the value in PC is a bool and the same as the DB value, skip it
                if (in_array($storeSettingsForStore[$storeProperty], ['0', '1', 0, 1, false, true, 'false', 'true'], true) && $storeSettingsForStore[$storeProperty] == $store[$storeProperty]) {
                    continue;
                }

                // If the value in PC is a string and is different from the DB value, skip it to avoid change in behavior
                $envVarValue = App::parseBooleanEnv($storeSettingsForStore[$storeProperty]);
                if ($envVarValue != $store[$storeProperty]) {
                    continue;
                }

                // Else update the DB with the environment variable name
                $updateData[$storeProperty] = $storeSettingsForStore[$storeProperty];
            }

            if (empty($updateData)) {
                continue;
            }

            $this->update(Table::STORES, $updateData, ['id' => $store['id']]);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m241204_091901_fix_store_environment_variables cannot be reverted.\n";
        return false;
    }
}
