<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\models;

use Craft;
use craft\commerce\base\HasStoreInterface;
use craft\commerce\base\Model;
use craft\commerce\base\StoreTrait;
use craft\commerce\base\TaxIdValidatorInterface;
use craft\commerce\Plugin;
use craft\commerce\records\TaxRate as TaxRateRecord;
use craft\errors\DeprecationException;
use DateTime;
use yii\base\InvalidConfigException;

/**
 * Tax Rate model.
 *
 * @property string $cpEditUrl
 * @property string $rateAsPercent
 * @property-read bool $isEverywhere
 * @property TaxAddressZone|null $taxZone
 * @property TaxCategory|null $taxCategory
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class TaxRate extends Model implements HasStoreInterface
{
    use StoreTrait;

    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var string|null Human-friendly name for the tax rate
     */
    public ?string $name = null;

    /**
     * @var string|null Optional code used for internal reference
     * @since 2.2
     */
    public ?string $code = null;

    /**
     * @var float Rate percentage applied to the taxable subject
     */
    public float $rate = .00;

    /**
     * @var bool Whether the tax amount should be included in the subject price
     */
    public bool $include = false;

    /**
     * @var bool Whether the included tax amount should be removed from disqualified subject prices
     * @since 3.4
     */
    public bool $removeIncluded = false;

    /**
     * @var bool Whether an included VAT ID tax amount should be removed from VAT-disqualified subject prices
     * @since 3.4
     */
    public bool $removeVatIncluded = false;

    /**
     * @var string The subject to which `$rate` should be applied. Options:
     *             - `price` – line item price
     *             - `shipping` – line item shipping cost
     *             - `price_shipping` – line item price and shipping cost
     *             - `order_total_shipping` – order total shipping cost
     *             - `order_total_price` – order total taxable price (line item subtotal + total discounts +
     *               total shipping)
     */
    public string $taxable = 'price';

    /**
     * @var int|null Tax category ID
     */
    public ?int $taxCategoryId = null;

    /**
     * @var int|null Tax zone ID
     */
    public ?int $taxZoneId = null;

    /**
     * @var array Tax ID Validators
     */
    public array $taxIdValidators = [];

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
     * @var bool Whether the tax rate is enabled
     */
    public bool $enabled = true;

    /**
     * @var TaxCategory|null
     */
    private ?TaxCategory $_taxCategory = null;

    /**
     * @var TaxAddressZone|null
     */
    private ?TaxAddressZone $_taxZone = null;

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $names = parent::attributes();
        $names[] = 'isVat'; // TODO remove in Commerce 6.x
        return $names;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();
        $rules[] = [['name'], 'required'];
        $rules[] = [
            ['taxCategoryId'],
            'required',
            'when' => function($model): bool {
                return !in_array($model->taxable, TaxRateRecord::ORDER_TAXABALES, true);
            },
        ];
        $rules[] = [[
            'code',
            'id',
            'include',
            'isVat',
            'name',
            'rate',
            'taxIdValidators',
            'removeIncluded',
            'removeVatIncluded',
            'storeId',
            'taxable',
            'taxCategoryId',
            'taxZoneId',
            'enabled',
        ], 'safe'];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function extraFields(): array
    {
        $fields = parent::extraFields();
        $fields[] = 'taxCategory';
        $fields[] = 'taxZone';
        $fields[] = 'rateAsPercent';
        $fields[] = 'isEverywhere';

        return $fields;
    }

    /**
     * Returns the tax rate’s control panel edit page URL.
     *
     * @return string
     * @throws InvalidConfigException
     */
    public function getCpEditUrl(): string
    {
        return $this->getStore()->getStoreSettingsUrl('taxrates/' . $this->id);
    }

    /**
     * Returns `$rate` formatted as a percentage.
     *
     * @return string
     */
    public function getRateAsPercent(): string
    {
        return Craft::$app->getFormatter()->asPercent($this->rate);
    }

    /**
     * Returns the designated Tax Zone for the rate, or `null` if none has been designated.
     *
     * @return TaxAddressZone|null
     * @throws InvalidConfigException
     */
    public function getTaxZone(): ?TaxAddressZone
    {
        if ($this->_taxZone === null && $this->taxZoneId) {
            $this->_taxZone = Plugin::getInstance()->getTaxZones()->getTaxZoneById($this->taxZoneId, $this->storeId);
        }

        return $this->_taxZone;
    }

    /**
     * Returns the designated Tax Category for the rate, or `null` if none has been designated.
     *
     * @return TaxCategory|null
     * @throws InvalidConfigException
     */
    public function getTaxCategory(): ?TaxCategory
    {
        if (!isset($this->_taxCategory) && $this->taxCategoryId) {
            $this->_taxCategory = Plugin::getInstance()->getTaxCategories()->getTaxCategoryById($this->taxCategoryId);
        }

        return $this->_taxCategory;
    }

    /**
     * Returns `true` is this tax rate isn’t limited by zone.
     *
     * @return bool Whether this tax rate applies to any zone
     * @throws InvalidConfigException
     */
    public function getIsEverywhere(): bool
    {
        return !$this->getTaxZone();
    }

    /**
     * @return bool
     * @deprecated in 5.3.0
     */
    public function getIsVat(): bool
    {
        // Don't throw deprecation log as `isVat` is still set as an attribute so will be called when the model is serialized.
        return $this->hasTaxIdValidators();
    }

    /**
     * @param bool $isVat
     * @throws DeprecationException
     * @deprecated in 5.3.0
     */
    public function setIsVat(bool $isVat): void
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'TaxRate::setIsVat() is deprecated.');
    }

    /**
     * @return bool
     * @since 5.3.0
     */
    public function hasTaxIdValidators(): bool
    {
        return count($this->taxIdValidators) > 0;
    }

    /**
     * @param string $className
     * @return bool
     * @since 5.3.0
     */
    public function hasTaxIdValidator(string $className): bool
    {
        return in_array($className, $this->taxIdValidators, true);
    }

    /**
     * @return TaxIdValidatorInterface[]
     * @throws InvalidConfigException
     * @since 5.3.0
     */
    public function getSelectedEnabledTaxIdValidators(): array
    {
        $selectedValidators = $this->taxIdValidators;
        $validators = Plugin::getInstance()->getTaxes()->getEnabledTaxIdValidators();
        $activeValidators = [];
        foreach ($validators as $validator) {
            if (in_array($validator::class, $selectedValidators)) {
                $activeValidators[] = $validator;
            }
        }
        return $activeValidators;
    }
}
