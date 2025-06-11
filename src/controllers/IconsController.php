<?php

namespace craftfm\iconify\controllers;

use Craft;
use craft\web\Controller;
use craft\web\Response;
use craftfm\iconify\jobs\DownloadIconsQueueJob;
use craftfm\iconify\Plugin;
use yii\web\BadRequestHttpException;

class IconsController extends Controller
{
    /**
     * @throws BadRequestHttpException
     */
    public function actionTriggerDownload(): \yii\web\Response
    {
        $iconSet = $this->request->getRequiredBodyParam('iconSet');
        $settings = Plugin::$plugin->getSettings();
        if ($iconSet === '*') {
            foreach ($settings->iconSets as $iconSet) {
                Craft::$app->queue->push(new DownloadIconsQueueJob([
                    'iconSet' => $iconSet,
                ]));
            }
        } else {
            Craft::$app->queue->push(new DownloadIconsQueueJob([
                'iconSet' => $iconSet,
            ]));
        }

        return $this->redirectToPostedUrl();
    }
}