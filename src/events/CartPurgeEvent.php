<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\events;

use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use craft\db\Query;
use craft\events\CancelableEvent;

/**
 * Class CartEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 2.0
 */
class CartPurgeEvent extends CancelableEvent
{
    /**
     * @var Query The query that identifies the order IDs to be purged.
     */
    public Query $inactiveCartsQuery;
}
