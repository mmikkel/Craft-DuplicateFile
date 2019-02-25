<?php
/**
 * Created by PhpStorm.
 * User: mmikkel
 * Date: 2019-02-25
 * Time: 08:21
 */

namespace mmikkel\duplicatefile\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Asset;
use craft\elements\db\ElementQueryInterface;
use craft\errors\InvalidElementException;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;

use yii\base\Exception;

class DuplicateFile extends ElementAction
{
    /**
     * @inheritdoc
     */
    public function getTriggerLabel(): string
    {
        return Craft::t('duplicate-file', 'Duplicate file');
    }

    /**
     * @inheritdoc
     */
    public function performAction(ElementQueryInterface $query): bool
    {

        /** @var Asset[] $assets */
        $assets = $query->all();
        $successCount = 0;
        $failCount = 0;

        $this->_duplicateAssets($assets, $successCount, $failCount);

        if (!$successCount) {
            $this->setMessage(Craft::t('duplicate-file', 'Could not duplicate files due to validation errors.'));
            return false;
        }

        if ($failCount > 0) {
            $this->setMessage(Craft::t('duplicate-file', 'Could not duplicate all files due to validation errors.'));
        } else {
            $this->setMessage(Craft::t('duplicate-file', 'Files duplicated.'));
        }

        return true;
    }

    /**
     * @param Asset[] $assets
     * @param int[] $duplicatedAssetIds
     * @param int $successCount
     * @param int $failCount
     */
    private function _duplicateAssets(array $assets, int &$successCount, int &$failCount, array &$duplicatedAssetIds = [])
    {

        $elementsService = Craft::$app->getElements();
        $assetsService = Craft::$app->getAssets();

        foreach ($assets as $asset) {

            if (isset($duplicatedAssetIds[$asset->id])) {
                continue;
            }

            try {

                $imageCopy = $asset->getCopyOfFile();
                if (!$imageCopy || !\file_exists($imageCopy)) {
                    throw new Exception('Can\'t copy file');
                }

                $folder = $asset->getFolder();
                $asset->setScenario(Asset::SCENARIO_CREATE);

                /* @var Asset $duplicate */
                $duplicate = $elementsService->duplicateElement($asset, [
                    'title' => Craft::t('app', '{title} copy', ['title' => $asset->title]),
                    'tempFilePath' => $imageCopy,
                    'filename' => $assetsService->getNameReplacementInFolder($asset->filename, $folder->id),
                    'avoidFilenameConflicts' => true,
                ]);

            } catch (\Throwable $e) {
                $failCount++;
                continue;
            }

            $successCount++;
            $duplicatedAssetIds[$asset->id] = true;

        }
    }
}
