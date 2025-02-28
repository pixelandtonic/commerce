<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\models;

use craft\commerce\base\Model;
use craft\commerce\behaviors\CurrencyAttributeBehavior;
use craft\commerce\elements\Order;
use craft\commerce\Plugin;
use craft\helpers\Json;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * Order adjustment model.
 *
 * @property Order|null $order
 * @property LineItem|null $lineItem
 * @property array $sourceSnapshot
 * @property-read string $currency
 * @property-read string $amountAsCurrency
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class OrderAdjustment extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var string Name
     */
    public string $name;

    /**
     * @var string|null Description
     */
    public ?string $description = null;

    /**
     * @var string Type
     */
    public string $type;

    /**
     * @var float Amount
     */
    public float $amount;

    /**
     * @var bool Included
     */
    public bool $included = false;

    /**
     * @var mixed Adjuster options
     */
    private mixed $_sourceSnapshot = [];

    /**
     * @var int|null Order ID
     */
    public ?int $orderId = null;

    /**
     * @var int|null Line item ID this adjustment belongs to
     */
    public ?int $lineItemId = null;

    /**
     * @var bool Whether the adjustment is based of estimated data
     */
    public bool $isEstimated = false;

    /**
     * @var LineItem|null The line item this adjustment belongs to
     */
    private ?LineItem $_lineItem = null;

    /**
     * @var Order|null The order this adjustment belongs to
     */
    private ?Order $_order = null;


    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        $behaviors['currencyAttributes'] = [
            'class' => CurrencyAttributeBehavior::class,
            'defaultCurrency' => $this->getCurrency(),
            'currencyAttributes' => $this->currencyAttributes(),
        ];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['type', 'amount', 'sourceSnapshot', 'orderId'], 'required'],
            [['amount'], 'number'],
            [['orderId'], 'integer'],
            [['lineItemId'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes[] = 'sourceSnapshot';

        return $attributes;
    }

    /**
     * The attributes on the order that should be made available as formatted currency.
     */
    public function currencyAttributes(): array
    {
        $attributes = [];
        $attributes[] = 'amount';
        return $attributes;
    }

    /**
     * @return ?string
     * @throws InvalidConfigException
     */
    protected function getCurrency(): ?string
    {
        return $this->getOrder()?->currency;
    }

    /**
     * Gets the options for the line item.
     */
    public function getSourceSnapshot(): array
    {
        return $this->_sourceSnapshot;
    }

    /**
     * Set the options array on the line item.
     */
    public function setSourceSnapshot(array|string $snapshot): void
    {
        if (is_string($snapshot)) {
            $snapshot = Json::decode($snapshot);
        }

        if (!is_array($snapshot)) {
            throw new InvalidArgumentException('Adjustment source snapshot must be an array.');
        }

        $this->_sourceSnapshot = $snapshot;
    }

    /**
     * @throws InvalidConfigException
     */
    public function getLineItem(): ?LineItem
    {
        if ($this->_lineItem === null && isset($this->lineItemId) && $this->lineItemId) {
            $this->_lineItem = Plugin::getInstance()->getLineItems()->getLineItemById($this->lineItemId);
        }

        return $this->_lineItem;
    }

    public function setLineItem(LineItem $lineItem): void
    {
        $this->_lineItem = $lineItem;
    }

    /**
     * @throws InvalidConfigException
     */
    public function getOrder(): ?Order
    {
        if (!isset($this->_order) && isset($this->orderId) && $this->orderId) {
            $this->_order = Plugin::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return $this->_order;
    }

    public function setOrder(Order $order): void
    {
        $this->_order = $order;
        $this->orderId = $order->id;
    }
}
