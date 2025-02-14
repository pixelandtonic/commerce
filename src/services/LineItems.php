<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\services;

use Craft;
use craft\commerce\db\Table;
use craft\commerce\elements\Order;
use craft\commerce\enums\LineItemType;
use craft\commerce\events\LineItemEvent;
use craft\commerce\helpers\LineItem as LineItemHelper;
use craft\commerce\models\LineItem;
use craft\commerce\Plugin;
use craft\commerce\records\LineItem as LineItemRecord;
use craft\db\Query;
use craft\errors\SiteNotFoundException;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\helpers\StringHelper;
use LitEmoji\LitEmoji;
use Throwable;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * Line item service.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class LineItems extends Component
{
    /**
     * @event LineItemEvent The event that is triggered before a line item is saved.
     *
     * ```php
     * use craft\commerce\events\LineItemEvent;
     * use craft\commerce\services\LineItems;
     * use craft\commerce\models\LineItem;
     * use yii\base\Event;
     *
     * Event::on(
     *     LineItems::class,
     *     LineItems::EVENT_BEFORE_SAVE_LINE_ITEM,
     *     function(LineItemEvent $event) {
     *         // @var LineItem $lineItem
     *         $lineItem = $event->lineItem;
     *         // @var bool $isNew
     *         $isNew = $event->isNew;
     *
     *         // Notify a third party service about changes to an order
     *         // ...
     *     }
     * );
     * ```
     */
    public const EVENT_BEFORE_SAVE_LINE_ITEM = 'beforeSaveLineItem';

    /**
     * @event LineItemEvent The event that is triggered after a line item is saved.
     *
     * ```php
     * use craft\commerce\events\LineItemEvent;
     * use craft\commerce\services\LineItems;
     * use craft\commerce\models\LineItem;
     * use yii\base\Event;
     *
     * Event::on(
     *     LineItems::class,
     *     LineItems::EVENT_AFTER_SAVE_LINE_ITEM,
     *     function(LineItemEvent $event) {
     *         // @var LineItem $lineItem
     *         $lineItem = $event->lineItem;
     *         // @var bool $isNew
     *         $isNew = $event->isNew;
     *
     *         // Reserve stock
     *         // ...
     *     }
     * );
     * ```
     */
    public const EVENT_AFTER_SAVE_LINE_ITEM = 'afterSaveLineItem';

    /**
     * @event LineItemEvent The event that is triggered after a line item has been created from a purchasable.
     *
     * ```php
     * use craft\commerce\events\LineItemEvent;
     * use craft\commerce\services\LineItems;
     * use craft\commerce\models\LineItem;
     * use yii\base\Event;
     *
     * Event::on(
     *     LineItems::class,
     *     LineItems::EVENT_CREATE_LINE_ITEM,
     *     function(LineItemEvent $event) {
     *         // @var LineItem $lineItem
     *         $lineItem = $event->lineItem;
     *         // @var bool $isNew
     *         $isNew = $event->isNew;
     *
     *         // Call a third party service based on the line item options
     *         // ...
     *     }
     * );
     * ```
     */
    public const EVENT_CREATE_LINE_ITEM = 'createLineItem';

    /**
     * @event LineItemEvent The event that is triggered as a line item is being populated from a purchasable.
     *
     * ```php
     * use craft\commerce\events\LineItemEvent;
     * use craft\commerce\services\LineItems;
     * use craft\commerce\models\LineItem;
     * use yii\base\Event;
     *
     * Event::on(
     *     LineItems::class,
     *     LineItems::EVENT_POPULATE_LINE_ITEM,
     *     function(LineItemEvent $event) {
     *         // @var LineItem $lineItem
     *         $lineItem = $event->lineItem;
     *         // @var bool $isNew
     *         $isNew = $event->isNew;
     *
     *         // Modify the price of a line item
     *         // ...
     *     }
     * );
     * ```
     */
    public const EVENT_POPULATE_LINE_ITEM = 'populateLineItem';

    /**
     * Returns an order's line items, per the order's ID.
     *
     * @param int $orderId the order's ID
     * @return LineItem[] An array of all the line items for the matched order.
     */
    public function getAllLineItemsByOrderId(int $orderId): array
    {
        $results = $this->_createLineItemQuery()
            ->where(['orderId' => $orderId])
            ->all();

        $lineItems = [];

        foreach ($results as $result) {
            $result['snapshot'] = Json::decodeIfJson($result['snapshot']);
            $lineItem = new LineItem($result);
            $lineItems[] = $lineItem;
        }

        return $lineItems;
    }

    /**
     * Takes an order, a purchasable ID, options, and resolves it to a line item.
     *
     * If a line item is found for that order ID with those exact options, that line item is
     * returned. Otherwise, a new line item is returned.
     *
     * @param Order $order
     * @param int $purchasableId the purchasable's ID
     * @param array $options Options for the line item
     * @return LineItem
     * @throws \Exception
     */
    public function resolveLineItem(Order $order, int $purchasableId, array $options = []): LineItem
    {
        $signature = LineItemHelper::generateOptionsSignature($options);

        $result = $order->id ? $this->_createLineItemQuery()
            ->where([
                'orderId' => $order->id,
                'purchasableId' => $purchasableId,
                'optionsSignature' => $signature,
            ])
            ->one() : null;

        if ($result) {
            $lineItem = new LineItem($result);
        } else {
            $lineItem = $this->create($order, compact('purchasableId', 'options'));
        }

        return $lineItem;
    }

    /**
     * @param Order $order
     * @param string $sku
     * @param array $options
     * @return LineItem
     * @throws Exception
     * @throws InvalidConfigException
     * @throws SiteNotFoundException
     * @since 5.1.0
     */
    public function resolveCustomLineItem(Order $order, string $sku, array $options = []): LineItem
    {
        $signature = LineItemHelper::generateOptionsSignature($options);

        $result = $order->id ? $this->_createLineItemQuery()
            ->where([
                'orderId' => $order->id,
                'sku' => $sku,
                'optionsSignature' => $signature,
                'type' => LineItemType::Custom->value,
            ])
            ->one() : null;

        if ($result) {
            $lineItem = new LineItem($result);
        } else {
            $lineItem = $this->create($order, [
                'sku' => $sku,
                'options' => $options,
            ], LineItemType::Custom);
        }

        return $lineItem;
    }

    /**
     * Save a line item.
     *
     * @param LineItem $lineItem The line item to save.
     * @param bool $runValidation Whether the Line Item should be validated.
     * @throws Throwable
     */
    public function saveLineItem(LineItem $lineItem, bool $runValidation = true): bool
    {
        $isNewLineItem = !$lineItem->id;

        if (!$lineItem->id) {
            $lineItemRecord = new LineItemRecord();
        } else {
            $lineItemRecord = LineItemRecord::findOne($lineItem->id);

            if (!$lineItemRecord) {
                throw new Exception(Craft::t('commerce', 'No line item exists with the ID “{id}”',
                    ['id' => $lineItem->id]));
            }
        }

        // Raise a 'beforeSaveLineItem' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_LINE_ITEM)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_LINE_ITEM, new LineItemEvent([
                'lineItem' => $lineItem,
                'isNew' => $isNewLineItem,
            ]));
        }

        if ($runValidation && !$lineItem->validate()) {
            Craft::info('Line item not saved due to validation error.', __METHOD__);
            return false;
        }

        $lineItemRecord->type = $lineItem->type->value;

        // Set the default for type dependent properties
        $lineItemRecord->hasFreeShipping = null;
        $lineItemRecord->isPromotable = null;
        $lineItemRecord->isShippable = null;
        $lineItemRecord->isTaxable = null;

        // Save this information for all line item types, even though live lookups will happen for line items with purchasables
        $lineItemRecord->hasFreeShipping = $lineItem->getHasFreeShipping();
        $lineItemRecord->isPromotable = $lineItem->getIsPromotable();
        $lineItemRecord->isShippable = $lineItem->getIsShippable();
        $lineItemRecord->isTaxable = $lineItem->getIsTaxable();

        $lineItemRecord->purchasableId = $lineItem->purchasableId;
        $lineItemRecord->orderId = $lineItem->orderId;
        $lineItemRecord->taxCategoryId = $lineItem->taxCategoryId;
        $lineItemRecord->shippingCategoryId = $lineItem->shippingCategoryId;
        $lineItemRecord->sku = $lineItem->getSku();
        $lineItemRecord->description = $lineItem->getDescription();

        $lineItemRecord->options = $lineItem->getOptions();
        $lineItemRecord->optionsSignature = $lineItem->getOptionsSignature();

        $lineItemRecord->qty = $lineItem->qty;
        $lineItemRecord->price = $lineItem->price;
        $lineItemRecord->promotionalPrice = $lineItem->promotionalPrice;

        $lineItemRecord->weight = $lineItem->weight;
        $lineItemRecord->width = $lineItem->width;
        $lineItemRecord->length = $lineItem->length;
        $lineItemRecord->height = $lineItem->height;

        $lineItemRecord->snapshot = $lineItem->getSnapshot();
        $lineItemRecord->note = LitEmoji::unicodeToShortcode($lineItem->note);
        $lineItemRecord->privateNote = LitEmoji::unicodeToShortcode($lineItem->privateNote);
        $lineItemRecord->lineItemStatusId = $lineItem->lineItemStatusId;

        $lineItemRecord->promotionalAmount = $lineItem->promotionalAmount;
        $lineItemRecord->salePrice = $lineItem->salePrice;
        $lineItemRecord->total = $lineItem->getTotal();
        $lineItemRecord->subtotal = $lineItem->getSubtotal();

        if ($lineItem->uid) {
            $lineItemRecord->uid = $lineItem->uid;
        }

        if (!$lineItem->hasErrors()) {
            $db = Craft::$app->getDb();
            $transaction = $db->beginTransaction();

            try {
                $success = $lineItemRecord->save(false);

                if ($success) {
                    $dateCreated = DateTimeHelper::toDateTime($lineItemRecord->dateCreated);
                    $dateUpdated = DateTimeHelper::toDateTime($lineItemRecord->dateUpdated);
                    $lineItem->dateCreated = $dateCreated;
                    $lineItem->dateUpdated = $dateUpdated;
                    $lineItem->uid = $lineItemRecord->uid;

                    if ($isNewLineItem) {
                        $lineItem->id = $lineItemRecord->id;
                    }

                    $transaction->commit();
                }
            } catch (Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }

            if ($success && $this->hasEventHandlers(self::EVENT_AFTER_SAVE_LINE_ITEM)) {
                $this->trigger(self::EVENT_AFTER_SAVE_LINE_ITEM, new LineItemEvent([
                    'lineItem' => $lineItem,
                    'isNew' => $isNewLineItem,
                ]));
            }

            return $success;
        }

        return false;
    }

    /**
     * Get a line item by its ID.
     *
     * @param int $id the line item ID
     * @return LineItem|null Line item or null, if not found.
     */
    public function getLineItemById(int $id): ?LineItem
    {
        $result = $this->_createLineItemQuery()
            ->where(['id' => $id])
            ->one();

        if ($result) {
            // Unpack the snapshot
            $result['snapshot'] = Json::decodeIfJson($result['snapshot']);
        }

        return $result ? new LineItem($result) : null;
    }

    /**
     * Create a line item.
     *
     * @param Order $order The order the line item is associated with
     * @param int $purchasableId The ID of the purchasable the line item represents
     * @param array $options Options to set on the line item
     * @param int $qty The quantity to set on the line item
     * @param string $note The note on the line item
     * @param string|null $uid
     * @throws \Exception
     * @deprecated in 5.1.0. Use [[create()]] instead.
     */
    public function createLineItem(Order $order, int $purchasableId, array $options, int $qty = 1, string $note = '', string $uid = null): LineItem
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'LineItems::createLineItem() has been deprecated. Use LineItems::create() instead.');
        $lineItem = new LineItem();
        $lineItem->qty = $qty;
        $lineItem->setOptions($options);
        $lineItem->note = $note;
        $lineItem->uid = $uid ?: StringHelper::UUID();
        $lineItem->setOrder($order);

        $forCustomer = $order->customerId ?? false;
        $purchasable = Plugin::getInstance()->getPurchasables()->getPurchasableById($purchasableId, $order->orderSiteId, $forCustomer);

        if ($purchasable) {
            $lineItem->setPurchasable($purchasable);
            $lineItem->populate($purchasable);
        } else {
            throw new InvalidArgumentException('Invalid purchasable ID');
        }

        // Raise a 'createLineItem' event
        if ($this->hasEventHandlers(self::EVENT_CREATE_LINE_ITEM)) {
            $this->trigger(self::EVENT_CREATE_LINE_ITEM, new LineItemEvent([
                'lineItem' => $lineItem,
                'isNew' => true,
            ]));
        }

        $lineItem->refresh();

        return $lineItem;
    }

    /**
     * @param Order $order
     * @param array $params
     * @param LineItemType $type
     * @return LineItem
     * @throws Exception
     * @throws SiteNotFoundException
     * @throws InvalidConfigException
     * @since 5.1.0
     */
    public function create(Order $order, array $params = [], LineItemType $type = LineItemType::Purchasable): LineItem
    {
        $params = array_merge([
            'qty' => 1,
            'options' => [],
            'note' => '',
            'uid' => StringHelper::UUID(),
        ], $params);

        $params['order'] = $order;
        $params['type'] = $type;

        if ($type === LineItemType::Purchasable && empty($params['purchasableId']) && empty($params['purchasable'])) {
            throw new InvalidArgumentException('Purchasable ID or Purchasable must be set');
        }

        $params['class'] = LineItem::class;
        /** @var LineItem $lineItem */
        $lineItem = Craft::createObject($params);

        if ($lineItem->type === LineItemType::Purchasable) {
            $purchasable = $lineItem->getPurchasable();

            if ($purchasable) {
                $lineItem->setPurchasable($purchasable);
                $lineItem->populate($purchasable);
            } else {
                throw new InvalidArgumentException('Invalid purchasable ID');
            }
        } else {
            $lineItem->populate();
        }

        // Raise a 'createLineItem' event
        if ($this->hasEventHandlers(self::EVENT_CREATE_LINE_ITEM)) {
            $this->trigger(self::EVENT_CREATE_LINE_ITEM, new LineItemEvent([
                'lineItem' => $lineItem,
                'isNew' => true,
            ]));
        }

        $lineItem->refresh();

        return $lineItem;
    }

    /**
     * Deletes all line items associated with an order, per the order's ID.
     *
     * @param int $orderId the order's ID
     * @return bool whether any line items were deleted
     */
    public function deleteAllLineItemsByOrderId(int $orderId): bool
    {
        return (bool)LineItemRecord::deleteAll(['orderId' => $orderId]);
    }

    /**
     * @param array|Order[] $orders
     * @return Order[]
     * @since 3.2.0
     */
    public function eagerLoadLineItemsForOrders(array $orders): array
    {
        $orderIds = ArrayHelper::getColumn($orders, 'id');
        $lineItemsResults = $this->_createLineItemQuery()->andWhere(['orderId' => $orderIds])->all();

        $lineItems = [];

        foreach ($lineItemsResults as $result) {
            $result['snapshot'] = Json::decodeIfJson($result['snapshot']);
            $lineItem = new LineItem($result);
            $lineItems[$lineItem->orderId] ??= [];
            $lineItems[$lineItem->orderId][] = $lineItem;
        }

        foreach ($orders as $key => $order) {
            if (isset($lineItems[$order->id])) {
                $order->setLineItems($lineItems[$order->id]);
                $orders[$key] = $order;
            }
        }

        return $orders;
    }

    /**
     *
     * @throws Throwable
     * @since 3.2.5
     */
    public function orderCompleteHandler(LineItem $lineItem, Order $order): void
    {
        // Called the after order complete method for the purchasable if there is one
        if ($lineItem->type === LineItemType::Purchasable && $lineItem->getPurchasable()) {
            $lineItem->getPurchasable()->afterOrderComplete($order, $lineItem);
        }

        // Retrieve the default status for the current line item. This is a chance for
        // developers to hook into an event for finer control
        $defaultStatus = Plugin::getInstance()->getLineItemStatuses()->getDefaultLineItemStatusForLineItem($lineItem);
        if (!$defaultStatus) {
            return;
        }

        // Set the status ID and save the line item
        $lineItem->setLineItemStatus($defaultStatus);
        $this->saveLineItem($lineItem, false);
    }

    /**
     * Returns a Query object prepped for retrieving line items.
     *
     * @return Query The query object.
     */
    private function _createLineItemQuery(): Query
    {
        return (new Query())
            ->select([
                'dateCreated',
                'dateUpdated',
                'description',
                'hasFreeShipping',
                'height',
                'id',
                'isPromotable',
                'isShippable',
                'isTaxable',
                'length',
                'lineItemStatusId',
                'note',
                'options',
                'orderId',
                'price',
                'promotionalPrice',
                'privateNote',
                'purchasableId',
                'qty',
                'shippingCategoryId',
                'sku',
                'snapshot',
                'taxCategoryId',
                'type',
                'uid',
                'weight',
                'width',
            ])
            ->from([Table::LINEITEMS . ' lineItems'])
            ->orderBy('dateCreated DESC');
    }
}
