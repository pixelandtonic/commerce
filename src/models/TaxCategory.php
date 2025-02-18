<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\models;

use craft\commerce\base\Model;
use craft\commerce\engines\Tax;
use craft\commerce\Plugin;
use craft\commerce\records\TaxCategory as TaxCategoryRecord;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use DateTime;
use yii\base\InvalidConfigException;

/**
 * Tax Category model.
 *
 * @property string $cpEditUrl
 * @property ProductType[] $productTypes
 * @property-read int[] $productTypeIds
 * @property array|TaxRate[] $taxRates
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class TaxCategory extends Model
{
    /**
     * @var int|null ID;
     */
    public ?int $id = null;

    /**
     * @var string|null Name
     */
    public ?string $name = null;

    /**
     * @var string|null Handle
     */
    public ?string $handle = null;

    /**
     * @var string|null Description
     */
    public ?string $description = null;

    /**
     * @var bool Default
     */
    public bool $default = false;

    /**
     * @var DateTime|null
     * @since 3.4
     */
    public ?DateTime $dateCreated = null;

    /**
     * @var DateTime|null
     * @since 3.4
     */
    public ?DateTime $dateUpdated = null;

    /**
     * @var DateTime|null Date deleted
     * @since 4.2.0.1
     */
    public ?DateTime $dateDeleted = null;

    /**
     * @var array|null Product Types
     */
    private ?array $_productTypes = null;


    /**
     * Returns the name of this tax category.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->name;
    }

    /**
     * @return TaxRate[]
     * @throws InvalidConfigException
     */
    public function getTaxRates(): array
    {
        $taxRates = [];

        foreach (Plugin::getInstance()->getTaxRates()->getAllTaxRates() as $rate) {
            if ($this->id === $rate->taxCategoryId) {
                $taxRates[] = $rate;
            }
        }

        return $taxRates;
    }

    public function getCpEditUrl(): string
    {
        return UrlHelper::cpUrl('commerce/tax/taxcategories/' . $this->id);
    }

    /**
     * @param ProductType[] $productTypes
     */
    public function setProductTypes(array $productTypes): void
    {
        $this->_productTypes = $productTypes;
    }

    /**
     * @return ProductType[]
     * @throws InvalidConfigException
     */
    public function getProductTypes(): array
    {
        if ($this->_productTypes === null && $this->id) {
            $this->_productTypes = Plugin::getInstance()->getProductTypes()->getProductTypesByTaxCategoryId($this->id);
        }

        return $this->_productTypes ?? [];
    }

    /**
     * Helper method to just get the product type IDs
     *
     * @return int[]
     * @throws InvalidConfigException
     */
    public function getProductTypeIds(): array
    {
        return ArrayHelper::getColumn($this->getProductTypes(), 'id');
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $engine = Plugin::getInstance()->getTaxes()->getEngine();
        $isStandardTaxEngine = $engine instanceof Tax;
        return [
            [['handle'], 'required'],
            [['handle'], UniqueValidator::class, 'targetClass' => TaxCategoryRecord::class],
            [['handle'], HandleValidator::class, 'when' => fn($model) => $isStandardTaxEngine],
        ];
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $fields = parent::extraFields();
        $fields[] = 'productTypes';
        $fields[] = 'productTypeIds';
        $fields[] = 'taxRates';

        return $fields;
    }
}
