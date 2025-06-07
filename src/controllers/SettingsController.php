<?php

namespace craftfm\iconify\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;
use craftfm\iconify\jobs\DownloadIconsQueueJob;
use craftfm\iconify\Plugin;
use Exception;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class SettingsController extends Controller
{
    // Only allow access from logged-in CP users
    protected array|int|bool $allowAnonymous = false;

    /**
     * @throws Exception
     */
    public function actionIndex(): Response
    {
        $collections = Plugin::getInstance()->iconify->getCategories();
        $settings = Plugin::getInstance()->getSettings();
        return $this->renderTemplate('iconify/_settings', [
            'settings' => $settings, 'collections' => $collections
        ]);
    }

    /**
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionSave(): ?Response
    {
        $iconSets = $this->request->getBodyParam('iconSets');

        $settings = Plugin::getInstance()->getSettings();
        $settings->iconSets = $iconSets ?: [];

        if (!$settings->validate()) {
            Craft::$app->getSession()->setError('Could not save plugin settings.');
            // Send data back to template (like redirect but keep state)
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);
            return null;
        }

        $pluginSettingsSaved = Craft::$app->getPlugins()->savePluginSettings(Plugin::$plugin, $settings->toArray());

        if (!$pluginSettingsSaved) {
            Craft::$app->getSession()->setError('Could not save plugin settings.');
            // Send data back to template (like redirect but keep state)
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);
            return null;
        }

        foreach ($settings->iconSets as $iconSet) {
            Craft::$app->queue->push(new DownloadIconsQueueJob([
                'iconSet' => $iconSet,
            ]));
        }
        Craft::$app->getSession()->setNotice('Settings saved.');
        return $this->redirectToPostedUrl();
    }
}