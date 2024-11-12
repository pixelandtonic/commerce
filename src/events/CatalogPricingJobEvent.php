<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\events;

use craft\commerce\base\Plan;
use craft\commerce\models\subscriptions\SubscriptionForm;
use craft\elements\User;
use craft\events\CancelableEvent;

/**
 * Class CreateSubscriptionEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class CatalogPricingJobEvent extends CancelableEvent
{
    /**
     * @var int[]|null
     */
    public ?array $purchasableIds = null;

    /**
     * @var int[]|null
     */
    public ?array $catalogPricingRuleIds = null;

    /**
     * @var int[]|null
     */
    public ?array $storeId = null;
}
