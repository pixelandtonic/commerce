<?php

namespace craft\commerce\elements;

use Craft;
use craft\base\Element;
use craft\commerce\collections\UpdateInventoryLevelCollection;
use craft\commerce\elements\conditions\transfers\TransferCondition;
use craft\commerce\elements\db\TransferQuery;
use craft\commerce\enums\InventoryTransactionType;
use craft\commerce\enums\InventoryUpdateQuantityType;
use craft\commerce\enums\TransferStatusType;
use craft\commerce\models\inventory\UpdateInventoryLevelInTransfer;
use craft\commerce\models\InventoryLocation;
use craft\commerce\models\TransferDetail;
use craft\commerce\Plugin;
use craft\commerce\records\Transfer as TransferRecord;
use craft\commerce\records\TransferDetail as TransferDetailRecord;
use craft\commerce\web\assets\transfers\TransfersAsset;
use craft\db\Query;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use craft\web\CpScreenResponseBehavior;
use yii\web\Response;

/**
 * Transfer element type
 *
 * @property TransferStatusType $transferStatus
 * @property ?int $originLocationId
 * @property ?int $destinationLocationId
 */
class Transfer extends Element
{
    /**
     * The status of the transfer status
     *
     * @var TransferStatusType
     */
    public TransferStatusType $transferStatus = TransferStatusType::DRAFT;

    /**
     * The origin location ID of the transfer
     *
     * @var ?int
     */
    public ?int $originLocationId = null;

    /**
     * The destination location ID of the transfer
     *
     * @var ?int
     */
    public ?int $destinationLocationId = null;

    /**
     * The transfer detail lines
     *
     * @var TransferDetail[]
     */
    public ?array $_details = null;

    /**
     * Returns the string representation of the element.
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->getOriginLocation() === null && $this->getDestinationLocation() === null) {
            return Craft::t('commerce', 'Transfer');
        }

        return (string)Craft::t('commerce', '{from} to {to}', [
            'from' => $this->getOriginLocation()->getUiLabel(),
            'to' => $this->getDestinationLocation()->getUiLabel(),
        ]);
    }

    public static function hasDrafts(): bool
    {
        return false;
    }

    protected function metadata(): array
    {
        $additionalMeta = [];

        $additionalMeta[] = [
            Craft::t('commerce', 'Transfer Status') => \craft\helpers\Cp::statusIndicatorHtml($this->getTransferStatus()->label(), [
                    'color' => $this->getTransferStatus()->color(),
                ]) . ' ' . Html::tag('span', $this->getTransferStatus()->label()),
        ];

        if ($this->getIsDraft() && !$this->isProvisionalDraft) {
            $additionalMeta[] = [
                Craft::t('app', 'Status') => function() {
                    $icon = Html::tag('span', '', [
                        'data' => ['icon' => 'draft'],
                        'aria' => ['hidden' => 'true'],
                    ]);
                    $label = Craft::t('app', 'Draft');
                    return $icon . Html::tag('span', $label);
                },
            ];
        }

        $additionalMeta[] = [
            Craft::t('commerce', 'Transfer Status') => \craft\helpers\Cp::statusIndicatorHtml($this->getTransferStatus()->label(), [
                    'color' => $this->getTransferStatus()->color(),
                ]) . ' ' . Html::tag('span', $this->getTransferStatus()->label()),
        ];

        return ArrayHelper::merge(parent::metadata(), ...$additionalMeta); // TODO: Change the autogenerated stub
    }


    /**
     * @return ?InventoryLocation
     * @throws \yii\base\InvalidConfigException
     */
    public function getOriginLocation(): ?InventoryLocation
    {
        if (!$this->originLocationId) {
            return null;
        }

        return Plugin::getInstance()->getInventoryLocations()->getInventoryLocationById($this->originLocationId);
    }

    /**
     * @return ?InventoryLocation
     * @throws \yii\base\InvalidConfigException
     */
    public function getDestinationLocation(): ?InventoryLocation
    {
        if (!$this->destinationLocationId) {
            return null;
        }

        return Plugin::getInstance()->getInventoryLocations()->getInventoryLocationById($this->destinationLocationId);
    }

    /**
     * @return TransferStatusType
     */
    public function getTransferStatus(): TransferStatusType
    {
        return $this->transferStatus;
    }

    /**
     * Updates the status to partial or received if all items have been received.
     *
     * @return void
     */
    public function updateTransferStatus(): void
    {
        // only pending can being partial or received.
        if ($this->isTransferDraft()) {
            return;
        } else {
            $this->setTransferStatus(TransferStatusType::PENDING);
        }

        if ($this->isAllReceived()) {
            $this->setTransferStatus(TransferStatusType::RECEIVED);
        }

        if ($this->getTotalReceived() > 0 && $this->getTotalReceived() < $this->getTotalQuantity()) {
            $this->setTransferStatus(TransferStatusType::PARTIAL);
        }
    }

    /**
     * @param TransferStatusType|string $status
     * @return void
     */
    public function setTransferStatus(TransferStatusType|string $status): void
    {
        if (is_string($status)) {
            $status = TransferStatusType::from($status);
        }

        $this->transferStatus = $status;
    }

    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return Craft::t('commerce', 'Transfer');
    }

    /**
     * @inheritDoc
     */
    public static function lowerDisplayName(): string
    {
        return Craft::t('commerce', 'transfer');
    }

    /**
     * @inheritDoc
     */
    public static function pluralDisplayName(): string
    {
        return Craft::t('commerce', 'Transfers');
    }

    /**
     * @inheritDoc
     */
    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('commerce', 'transfers');
    }

    /**
     * @inheritDoc
     */
    public static function refHandle(): ?string
    {
        return 'transfer';
    }

    /**
     * @inheritDoc
     */
    public static function trackChanges(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function hasTitles(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function hasContent(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public static function hasUris(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function isLocalized(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public static function hasStatuses(): bool
    {
        return false;
    }

    /**
     * @return TransferQuery
     * @inheritDoc
     */
    public static function find(): ElementQueryInterface
    {
        return Craft::createObject(TransferQuery::class, [static::class]);
    }

    /**
     * @inheritDoc
     */
    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(TransferCondition::class, [static::class]);
    }

    /**
     * @inheritDoc
     */
    protected static function includeSetStatusAction(): bool
    {
        return false;
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'slug' => Craft::t('app', 'Slug'),
            'uri' => Craft::t('app', 'URI'),
            [
                'label' => Craft::t('app', 'Date Created'),
                'orderBy' => 'elements.dateCreated',
                'attribute' => 'dateCreated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'Date Updated'),
                'orderBy' => 'elements.dateUpdated',
                'attribute' => 'dateUpdated',
                'defaultDir' => 'desc',
            ],
            [
                'label' => Craft::t('app', 'ID'),
                'orderBy' => 'elements.id',
                'attribute' => 'id',
            ],
            // ...
        ];
    }

    /**
     * @inheritDoc
     */
    protected static function defineTableAttributes(): array
    {
        return [
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
            'originLocation' => ['label' => Craft::t('commerce', 'Origin')],
            'destinationLocation' => ['label' => Craft::t('commerce', 'Destination')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            'received' => ['label' => Craft::t('commerce', 'Received')],
        ];
    }

    /**
     * @inheritDoc
     */
    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'id',
            'dateCreated',
            'received',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'originLocation':
            {
                return $this->getOriginLocation()?->getUiLabel() ?? '';
            }
            case 'destinationLocation':
            {
                return $this->getDestinationLocation()?->getUiLabel() ?? '';
            }
            case 'received':
            {
                return $this->getTotalReceived() . '/' . $this->getTotalQuantity();
            }
            default:
            {
                return parent::attributeHtml($attribute);
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        if ($this->scenario == static::SCENARIO_LIVE) {
            $rules = ArrayHelper::merge($rules, [
                [['originLocationId', 'destinationLocationId'], 'number', 'integerOnly' => true],
                [['originLocationId', 'destinationLocationId'], 'required'],
            ]);

            $rules[] = [['originLocationId'], 'validateLocations'];
            $rules[] = [['details'], 'validateDetails'];
        }

        return $rules;
    }

    /**
     * @param $attribute
     * @param $params
     * @param $validator
     * @return void
     */
    public function validateDetails($attribute, $params, $validator)
    {
        if ($this->sumDetailsQuanity() < 1) {
            $this->addError($attribute, Craft::t('commerce', 'Transfer must have at least one item.'));
        }

        foreach ($this->getDetails() as $detail) {
            if (!$detail->validate()) {
                $this->addModelErrors($detail, 'details');
            }
        }
    }

    /**
     * @param $attribute
     * @param $params
     * @param $validator
     * @return void
     */
    public function validateLocations($attribute, $params, $validator)
    {
        if ($this->originLocationId == $this->destinationLocationId) {
            $this->addError($attribute, Craft::t('commerce', 'Origin and destination cannot be the same.'));
        }
    }

    /**
     * @inheritDoc
     */
    public function getUriFormat(): ?string
    {
        return null;
    }

    /**
     * Define the sources for the transfer element index
     *
     * @param string|null $context
     * @return array
     */
    protected static function defineSources(string $context = null): array
    {
        $transferStatuses = TransferStatusType::cases();
        $transferStatusSources = [];
        foreach ($transferStatuses as $status) {
            $transferStatusSources[] = [
                'key' => $status->value,
                'status' => $status->color(),
                'label' => Craft::t('commerce', $status->label()),
                'badgeCount' => Transfer::find()->transferStatus($status->value)->count(),
                'criteria' => [
                    'transferStatus' => $status->value,
                ],
            ];
        }

        return [
            [
                'key' => '*',
                'label' => Craft::t('commerce', 'All Transfers'),
                'criteria' => [],
            ],
            [
                'heading' => Craft::t('commerce', 'Transfer Status'),
            ],
            ...$transferStatusSources,
        ];
    }

    /**
     *
     * @inheritDoc
     */
    protected function previewTargets(): array
    {
        $previewTargets = [];
        $url = $this->getUrl();
        if ($url) {
            $previewTargets[] = [
                'label' => Craft::t('app', 'Primary {type} page', [
                    'type' => self::lowerDisplayName(),
                ]),
                'url' => $url,
            ];
        }
        return $previewTargets;
    }

    protected function safeActionMenuItems(): array
    {
        $safeActions = parent::safeActionMenuItems();

        if ($this->isTransferDraft() && count($this->getDetails()) > 0) {
            $safeActions['mark-as-pending'] = [
                'action' => 'commerce/transfers/mark-as-pending',
                'label' => Craft::t('commerce', 'Mark as Pending'),
                'confirm' => Craft::t('commerce', 'Are you sure you want to mark this transfer as pending? This will show as incoming at the destination.'),
                'params' => [
                    'transferId' => $this->id,
                ],
                'redirect' => 'commerce/inventory/transfers/' . $this->id,
            ];
        }

        return $safeActions;
    }


    /**
     * @inheritDoc
     */
    protected function route(): array|string|null
    {
        // Define how transfers should be routed when their URLs are requested
        return [
            'templates/render',
            [
                'template' => 'site/template/path',
                'variables' => ['transfer' => $this],
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return $user->can('commerce-manageTransfers');
    }

    /**
     * @inheritDoc
     */
    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        return $user->can('commerce-manageTransfers');
    }

    /**
     * @inheritDoc
     */
    public function canDuplicate(User $user): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canDelete(User $user): bool
    {
        $canDelete = false;

        if (parent::canSave($user)) {
            $canDelete = true;
        }

        if ($this->getTransferStatus() === TransferStatusType::DRAFT) {
            $canDelete = true;
        }

        return $canDelete && $user->can('commerce-manageTransfers');
    }

    /**
     * @inheritDoc
     */
    public function canCreateDrafts(User $user): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl("commerce/inventory/transfers/{$this->getCanonicalId()}");
    }

    /**
     * @inheritDoc
     */
    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('commerce/inventory/transfers');
    }

    /**
     * @inheritDoc
     */
    public function prepareEditScreen(Response $response, string $containerId): void
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(TransfersAsset::class);

        $view->registerJsWithVars(fn($containerId, $settingsJs) => <<<JS
new Craft.Commerce.TransferEdit($('#' + $containerId), $settingsJs);
JS, [
            $containerId,
            [],
        ]);

        $receiveInventoryButtonId = sprintf("receive-transfer-%s", mt_rand());

        $view->registerJsWithVars(fn($id, $settings) => <<<JS
$('#' + $id).on('click', (e) => {
	e.preventDefault();
	const modal = new Craft.Commerce.ReceiveTransferScreen($settings);
	modal.on('close', (e) => {
	  console.log('closed');
	});
});
JS, [
            $receiveInventoryButtonId,
            ['params' => ['transferId' => $this->id]],
        ]);

        if (!$this->isTransferDraft()) {

            /** @var Response|CpScreenResponseBehavior $response */
            $response->additionalButtonsHtml(Html::a(
                Craft::t('commerce', 'Receive Inventory'),
                '#',
                [
                    'id' => $receiveInventoryButtonId,
                    'class' => 'btn',
                ]
            ));
        }

        /** @var Response|CpScreenResponseBehavior $response */
        $response->crumbs([
            [
                'label' => Craft::t('commerce', 'Inventory'),
                'url' => UrlHelper::cpUrl('commerce/inventory'),
            ],
            [
                'label' => self::pluralDisplayName(),
                'url' => UrlHelper::cpUrl('commerce/inventory/transfers'),
            ],
        ]);
    }

    /**
     * @return TransferDetail[]
     */
    public function getDetails(): array
    {
        if ($this->_details === null) {
            $this->_details = Plugin::getInstance()->getTransfers()->getTransferDetailsByTransferId($this->id);
        }

        return $this->_details;
    }

    public function addDetails(TransferDetail $details): void
    {
        $this->_details = $this->getDetails();
        $this->_details[] = $details;
    }

    /**
     * @param TransferDetail[]|array $value
     *
     * @return void
     */
    public function setDetails(array $value): void
    {
        foreach ($value as $key => $detail) {
            if (!$detail instanceof TransferDetail) {
                $value[$key] = new TransferDetail($detail);
            }

            $value[$key]->setTransfer($this);

            if (!$value[$key]->inventoryItemId) {
                unset($value[$key]);
            }
        }

        $this->_details = $value;
    }

    /**
     * @return int
     */
    public function sumDetailsQuanity(): int
    {
        $sum = 0;
        foreach ($this->getDetails() as $detail) {
            $sum += $detail->quantity;
        }
        return $sum;
    }

    /**
     * @param TransferDetail $detail
     * @return void
     */
    public function addDetail(TransferDetail $detail): void
    {
        if (!$this->_details) {
            $this->_details = [];
        }

        foreach ($this->_details as $existingDetail) {
            if ($existingDetail->inventoryItemId == $detail->inventoryItemId) {
                $existingDetail->quantity += $detail->quantity;
                return;
            }
        }

        $this->_details[] = $detail;
    }

    /**
     * @inheritDoc
     */
    public function getFieldLayout(): ?FieldLayout
    {
        return Plugin::getInstance()->getTransfers()->getFieldLayout();
    }

    /**
     * @inheritDoc
     */
    public function beforeValidate()
    {
        if ($this->transferStatus === null) {
            $this->transferStatus = TransferStatusType::DRAFT;
        }

        return parent::beforeValidate();
    }

    /**
     * @inheritDoc
     */
    public function afterSave(bool $isNew): void
    {
        if (!$this->propagating) {
            $transferId = $this->getCanonicalId();
            $transferRecord = TransferRecord::findOne($transferId);

            if (!$transferRecord) {
                $transferRecord = new TransferRecord();
            }

            $originalTransferStatus = $transferRecord->transferStatus;

            $transferRecord->id = $this->id;
            $transferRecord->originLocationId = $this->originLocationId;
            $transferRecord->destinationLocationId = $this->destinationLocationId;
            $transferRecord->transferStatus = $this->getTransferStatus()->value ?? TransferStatusType::DRAFT->value;

            $transferRecord->save(false);

            if ($this->getTransferStatus() === TransferStatusType::PENDING && $originalTransferStatus == TransferStatusType::DRAFT->value) {
                $inventoryUpdateCollection = new UpdateInventoryLevelCollection();
                foreach ($this->getDetails() as $detail) {
                    $inventoryUpdate1 = new UpdateInventoryLevelInTransfer();
                    $inventoryUpdate1->type = InventoryTransactionType::INCOMING->value;
                    $inventoryUpdate1->updateAction = InventoryUpdateQuantityType::ADJUST;
                    $inventoryUpdate1->inventoryItemId = $detail->inventoryItemId;
                    $inventoryUpdate1->transferId = $this->id;
                    $inventoryUpdate1->inventoryLocationId = $this->destinationLocationId;
                    $inventoryUpdate1->quantity = $detail->quantity;
                    $inventoryUpdate1->note = Craft::t('commerce', 'Incoming transfer from Transfer ID: ') . $this->id;

                    $inventoryUpdateCollection->push($inventoryUpdate1);

                    $inventoryUpdate2 = new UpdateInventoryLevelInTransfer();
                    $inventoryUpdate2->type = 'onHand';
                    $inventoryUpdate2->updateAction = InventoryUpdateQuantityType::ADJUST;
                    $inventoryUpdate2->inventoryItemId = $detail->inventoryItemId;
                    $inventoryUpdate2->transferId = $this->id;
                    $inventoryUpdate2->inventoryLocationId = $this->originLocationId;
                    $inventoryUpdate2->quantity = $detail->quantity * -1;
                    $inventoryUpdate2->note = Craft::t('commerce', 'Outgoing transfer from Transfer ID: ') . $this->id;

                    $inventoryUpdateCollection->push($inventoryUpdate2);
                }

                Plugin::getInstance()->getInventory()->executeUpdateInventoryLevels($inventoryUpdateCollection);
            }

            $existingDetailIds = (new Query())
                ->select('id')
                ->from('{{%commerce_transferdetails}}')
                ->where(['transferId' => $this->id])
                ->column();

            $currentDetailIds = [];

            foreach ($this->getDetails() as $detail) {
                if ($detail->id) {
                    $detailRecord = TransferDetailRecord::findOne($detail->id);
                } else {
                    $detailRecord = new TransferDetailRecord();
                }
                $detailRecord->transferId = $this->id;
                $detailRecord->inventoryItemId = $detail->inventoryItemId;
                $inventoryItem = $detail->inventoryItemId ? Plugin::getInstance()->getInventory()->getInventoryItemById($detail->inventoryItemId) : null;
                $detailRecord->inventoryItemDescription = $inventoryItem?->sku ?? '';
                $detailRecord->quantity = $detail->quantity;
                $detailRecord->quantityAccepted = $detail->quantityAccepted;
                $detailRecord->quantityRejected = $detail->quantityRejected;

                $detailRecord->save();
                $detail->id = $detailRecord->id;

                $currentDetailIds[] = $detailRecord->id;
            }

            $deletedDetailIds = array_diff($existingDetailIds, $currentDetailIds);
            if (!empty($deletedDetailIds)) {
                TransferDetailRecord::deleteAll(['id' => $deletedDetailIds]);
            }

            $this->updateTransferStatus();
            $transferRecord->transferStatus = $this->getTransferStatus()->value;

            $transferRecord->save(false);
        }

        parent::afterSave($isNew);
    }

    /**
     * @return bool
     */
    public function isTransferDraft(): bool
    {
        return $this->getTransferStatus() === TransferStatusType::DRAFT;
    }

    /**
     * @return bool
     */
    public function isTransferPending(): bool
    {
        return $this->getTransferStatus() === TransferStatusType::PENDING;
    }

    /**
     * @return bool
     */
    public function isTransferPartial(): bool
    {
        return $this->getTransferStatus() === TransferStatusType::PARTIAL;
    }

    /**
     * @return bool
     */
    public function isTransferReceived(): bool
    {
        return $this->getTransferStatus() === TransferStatusType::RECEIVED;
    }

    /**
     * @return int
     */
    public function getTotalRejected(): int
    {
        $totalRejected = 0;
        foreach ($this->getDetails() as $detail) {
            $totalRejected += $detail->quantityRejected;
        }
        return $totalRejected;
    }

    /**
     * @return int
     */
    public function getTotalAccepted(): int
    {
        $totalAccepted = 0;
        foreach ($this->getDetails() as $detail) {
            $totalAccepted += $detail->quantityAccepted;
        }
        return $totalAccepted;
    }

    /**
     * @return int
     */
    public function getTotalReceived(): int
    {
        return $this->getTotalAccepted() + $this->getTotalRejected();
    }

    /**
     * @return bool
     */
    public function isAllReceived(): bool
    {
        foreach ($this->getDetails() as $detail) {
            if ($detail->getReceived() < $detail->quantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return int
     */
    public function getTotalQuantity(): int
    {
        $totalQuantity = 0;
        foreach ($this->getDetails() as $detail) {
            $totalQuantity += $detail->quantity;
        }
        return $totalQuantity;
    }
}
