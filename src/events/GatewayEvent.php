<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\commerce\events;

use craft\commerce\base\Gateway;
use yii\base\Event;

/**
 * Class GatewayEvent
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.4.0
 */
class GatewayEvent extends Event
{
    /**
     * @var Gateway The gateway model associated with the event.
     */
    public Gateway $pdf;

    /**
     * @var bool Whether the Gateway is brand new
     */
    public bool $isNew = false;
}
