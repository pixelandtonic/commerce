<?php

namespace craft\commerce\migrations;

use Craft;
use craft\commerce\db\Table;
use craft\db\Migration;
use craft\db\Query;
use craft\helpers\Json;

/**
 * m250129_080909_fix_discount_conditions migration.
 */
class m250129_080909_fix_discount_conditions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $primaryStoreId = (new Query())
            ->select('id')
            ->from(Table::STORES)
            ->where(['primary' => true])
            ->scalar();

        // get orderCondition from craft_commerce_discounts table
        $discounts = (new Query())
            ->select(['id', 'orderCondition'])
            ->from(Table::DISCOUNTS)
            ->pairs();

        foreach ($discounts as $discountId => $orderCondition) {
            $orderConditionData = Json::decodeIfJson($orderCondition);

            if (!is_array($orderConditionData)) {
                continue;
            }

            if (!isset($orderConditionData['storeId'])) {
                $orderConditionData['storeId'] = $primaryStoreId;
                $orderConditionJson = Json::encode($orderConditionData);
                $this->update(Table::DISCOUNTS,
                    ['orderCondition' => $orderConditionJson],
                    ['id' => $discountId]
                );
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m250129_080909_fix_discount_conditions cannot be reverted.\n";
        return false;
    }
}
