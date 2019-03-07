<?php
/**
 * Duplicate File plugin for Craft CMS 3.x
 *
 * Adds a Duplicate File element action
 *
 * Icon: duplicate file by StoneHub from the Noun Project
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2019 Mats Mikkel Rummelhoff
 */

namespace mmikkel\duplicatefile;

use mmikkel\duplicatefile\elements\actions\DuplicateFile as DuplicateFileAction;

use Craft;
use craft\base\Element;
use craft\base\Plugin;
use craft\elements\Asset;
use craft\elements\Assets;
use craft\events\RegisterElementActionsEvent;
use craft\helpers\StringHelper;
use craft\services\Plugins;
use craft\events\PluginEvent;

use yii\base\Event;

/**
 * Class DuplicateFile
 *
 * @author    Mats Mikkel Rummelhoff
 * @package   DuplicateFile
 * @since     1.0.0
 *
 */
class DuplicateFile extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var DuplicateFile
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            Plugins::class,
            Plugins::EVENT_AFTER_LOAD_PLUGINS,
            [$this, 'onAfterLoadPlugins']
        );

        Craft::info(
            Craft::t(
                'duplicate-file',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================
    /**
     *
     */
    public function onAfterLoadPlugins()
    {

        $version = Craft::$app->getVersion();
        if (!\version_compare($version, '3.1.0', '>=')) {
            return;
        }

        Event::on(Asset::class, Element::EVENT_REGISTER_ACTIONS, function (RegisterElementActionsEvent $event) {
            $source = $event->source;

            if (!\preg_match('/^folder:([a-z0-9\-]+)/', $source, $matches)) {
                return;
            }

            $folderUid = $matches[1] ?? null;
            if (!StringHelper::isUUID($folderUid)) {
                return;
            }

            $folder = Craft::$app->getAssets()->getFolderByUid($folderUid);
            if (!$folder) {
                return;
            }

            /** @var Volume $volume */
            $volume = $folder->getVolume();
            $permission = "saveAssetInVolume:{$volume->uid}";
            if (!Craft::$app->getUser()->checkPermission($permission)) {
                return;
            }
            $event->actions[] = new DuplicateFileAction();
        });
    }

}
