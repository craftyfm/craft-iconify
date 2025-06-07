<?php

namespace craftfm\iconify\console\controllers;

use Craft;
use craft\console\Controller;
use craftfm\iconify\jobs\DownloadIconsQueueJob;
use craftfm\iconify\models\Icon;
use craftfm\iconify\Plugin;
use GuzzleHttp\Exception\GuzzleException;
use yii\base\ErrorException;
use yii\console\ExitCode;
use yii\db\Exception;

class DownloadIconsController extends Controller
{
    /**
     * @throws Exception
     * @throws GuzzleException
     * @throws ErrorException
     * @throws \yii\base\Exception
     */
    public function actionIndex(?string $iconSet = null): int
    {
        $settings = Plugin::$plugin->getSettings();
        if ($iconSet === '*' || $iconSet === null) {
            foreach ($settings->iconSets as $set) {
                $this->_downloadIcon($set);
            }
        } else {
            $this->_downloadIcon($iconSet);
        }

        return ExitCode::OK;
    }

    /**
     * @throws Exception
     * @throws ErrorException
     * @throws GuzzleException
     * @throws \yii\base\Exception
     */
    private function _downloadIcon($iconSet): void
    {
        $icons = Plugin::getInstance()->iconify->getIconList($iconSet);
        $batch = Plugin::getInstance()->iconify->batchIconSet($iconSet, $icons);
        Plugin::getInstance()->icons->deleteIconSet($iconSet);
        $total = count($batch);
        $iconData = [];
        for ($i = 0; $i < $total; $i++) {
            $iconBody = Plugin::getInstance()->iconify->getIconsData($iconSet, array_keys($batch[$i]));
            $iconData = array_merge($iconData, $iconBody);
            foreach ($iconBody as $key => $value) {
                $icon = new Icon();
                $icon->name = $key;
                $icon->set = $iconSet;
                $icon->body = $value['body'];
                $icon->prefix = $batch[$i][$key]['prefix'] ?? null;
                $icon->suffix = $batch[$i][$key]['suffix'] ?? null;
                Plugin::getInstance()->icons->saveIcon($icon);
            }
        }
    }
}