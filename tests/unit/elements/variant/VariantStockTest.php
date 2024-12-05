<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace unit\elements\variant;

use Codeception\Test\Unit;
use craft\commerce\elements\Variant;
use craft\commerce\enums\InventoryUpdateQuantityType;
use craftcommercetests\fixtures\ProductFixture;

/**
 * VariantStockTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class VariantStockTest extends Unit
{
    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'products' => [
                'class' => ProductFixture::class,
            ],
        ];
    }

    /**
     * @param array $updateConfigs
     * @return void
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @dataProvider setStockLevelDataProvider
     */
    public function testSetStockLevel(array $updateConfigs, int $expected): void
    {
        $variant = Variant::find()->sku('rad-hood')->one();
        $originalStock = $variant->getStock();

        foreach ($updateConfigs as $updateConfig) {
            $qty = $updateConfig['quantity'];
            unset($updateConfig['quantity']);

            $variant->setStockLevel($qty, $updateConfig);
        }

        self::assertEquals($expected, $variant->getStock());

        $variant->setStockLevel($originalStock);
    }

    /**
     * @return array[]
     */
    public function setStockLevelDataProvider(): array
    {
        return [
            'simple-single-arg' => [
                [
                    ['quantity' => 10],
                ],
                'expected' => 10,
            ],
            'set-and-adjust' => [
                [
                    ['quantity' => 10],
                    ['quantity' => 2, 'updateAction' => InventoryUpdateQuantityType::ADJUST],
                ],
                'expected' => 12,
            ],
            'just-adjust' => [
                [
                    ['quantity' => 2, 'updateAction' => InventoryUpdateQuantityType::ADJUST],
                ],
                'expected' => 2,
            ],
            'set-and-adjust-negative' => [
                [
                    ['quantity' => 10],
                    ['quantity' => -2, 'updateAction' => InventoryUpdateQuantityType::ADJUST],
                ],
                'expected' => 8,
            ],
        ];
    }
}
