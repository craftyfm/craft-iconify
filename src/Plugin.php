<?php

namespace craftfm\iconify;

use Craft;
use craft\base\Model;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterComponentTypesEvent;
use craft\helpers\UrlHelper;
use craft\services\Fields;
use craft\services\Utilities;
use craft\web\UrlManager;
use craftfm\iconify\fields\IconifyPicker;
use craftfm\iconify\models\Settings;
use craftfm\iconify\services\Iconify;
use craftfm\iconify\services\Icons;
use craftfm\iconify\utilities\DownloadIconsUtility;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\web\Response;

/**
 * Huge Icon Library plugin
 *
 * @method static Plugin getInstance()
 * @method Settings getSettings()
 * @property Iconify $iconify
 * @property Icons $icons
 * @author Stuart Russell
 * @copyright Stuart Russell
 * @license MIT
 */
class Plugin extends BasePlugin
{
    public static Plugin $plugin;
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;


    public function init(): void
    {
        self::$plugin = $this;
        parent::init();
        Craft::setAlias('@craftyfm/iconify', $this->getBasePath());

        $this->setComponents([
            'iconify' => Iconify::class,
            'icons' => Icons::class,
        ]);

        $this->attachEventHandlers();

        // Any code that creates an element query or loads Twig should be deferred until
        // after Craft is fully initialized, to avoid conflicts with other plugins/modules
        Craft::$app->onInit(function() {
            // ...
        });
    }

    /**
     * @throws InvalidConfigException
     */
    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }


    public function getSettingsResponse(): Response
    {
        $url = UrlHelper::cpUrl('iconify/settings');

        return Craft::$app->controller->redirect($url);
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function($event) {
                $event->rules['iconify/settings'] = 'iconify/settings/index';
            }
        );

        $extension = new twigextentions\IconsExtension();
        Craft::$app->getView()->registerTwigExtension($extension);

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = IconifyPicker::class;
            }
        );

        Event::on(
            Utilities::class,
            Utilities::EVENT_REGISTER_UTILITIES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = DownloadIconsUtility::class;
            }
        );
    }
}
