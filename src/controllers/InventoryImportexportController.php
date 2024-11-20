<?php

namespace craft\commerce\controllers;

use Craft;
use craft\commerce\models\InventoryImport;
use craft\commerce\Plugin;
use craft\errors\DeprecationException;
use craft\web\Controller;
use craft\web\UploadedFile;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\Response;

/**
 * Inventory Importexport controller
 */
class InventoryImportexportController extends Controller
{
    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    /**
     * commerce/inventory-importexport action
     */
    public function actionIndex(): Response
    {
        $params = [];

        return $this->asCpScreen()
            ->action('commerce/inventory/import-inventory')
            ->addCrumb(Craft::t('commerce', 'Inventory'), 'commerce/inventory')
            ->selectedSubnavItem('inventory')
            ->title(Craft::t('commerce', 'Import Inventory'))
            ->formAttributes(['enctype' => 'multipart/form-data'])
            ->metaSidebarTemplate('commerce/inventory/importexport/_importMeta')
            ->submitButtonLabel(Craft::t('commerce', 'Import'))
            ->contentTemplate('commerce/inventory/importexport/_importScreen', $params);
    }

    public function actionImportInventory(): Response
    {
        $errors = [];
        $inventory = Plugin::getInstance()->getInventory();
        $this->requirePostRequest();
        $this->requirePermission('commerce-manageInventoryStockLevels');

        $file = UploadedFile::getInstanceByName('importFile');

        if (!$file) {
            return $this->asError(Craft::t('commerce', 'No file uploaded.'));
        }

        $import = new InventoryImport([
            'importFile' => $file->tempName
        ]);

        $inventory->importInventory($import);


        return $this->asSuccess(Craft::t('commerce', 'Inventory imported.'));
    }

    /**
     * @return Response
     * @throws InvalidConfigException
     * @throws \yii\web\ForbiddenHttpException
     * @throws \yii\web\HttpException
     * @throws \yii\web\RangeNotSatisfiableHttpException
     */
    public function actionExport(): Response{
        $this->requirePermission('commerce-manageInventoryStockLevels');

        $inventoryLocationId = (int)Craft::$app->getRequest()->getParam('inventoryLocationId');
        $inventoryLocation = Plugin::getInstance()->getInventoryLocations()->getInventoryLocationById($inventoryLocationId);

        $inventoryLevels = Plugin::getInstance()->getInventory()->getInventoryLevelsForLocation($inventoryLocation);

        $csv = Plugin::getInstance()->getInventory()->exportInventoryLevelsToCsv($inventoryLevels);

        return Craft::$app->getResponse()->sendContentAsFile($csv, 'inventory-export.csv', ['mimeType' => 'text/csv']);
    }


}
