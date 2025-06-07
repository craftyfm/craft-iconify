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
                $this->_processIconSet($set);
            }
        } else if(in_array($iconSet, $settings->iconSets)) {
            $this->_processIconSet($iconSet);
        }

        return ExitCode::OK;
    }

    /**
     * @throws Exception
     * @throws ErrorException
     * @throws GuzzleException
     * @throws \yii\base\Exception
     */
    private function _processIconSet($iconSet): void
    {
        $iconList = Plugin::getInstance()->iconify->getIconList($iconSet);
        Plugin::getInstance()->icons->deleteIconSet($iconSet);

        $prefixes = [];
        $suffixes = [];
        foreach ($iconList['prefixes'] as $prefix => $label) {
            $id = Plugin::getInstance()->icons->saveIconAffix($iconSet, $prefix, $label, 'prefix');
            $prefixes[$prefix] = $id;
        }

        foreach ($iconList['suffixes'] as $suffix => $label) {
            $id = Plugin::getInstance()->icons->saveIconAffix($iconSet, $suffix, $label, 'suffix');
            $suffixes[$suffix] = $id;
        }
        $this->_saveIcons($iconSet, $iconList['icons'], $prefixes, $suffixes);
    }

    /**
     * @throws Exception
     * @throws \yii\base\Exception
     * @throws GuzzleException
     */
    private function _saveIcons(string $iconSet, array $iconList, array $prefixes, array $suffixes): void
    {
        $sf = array_keys($suffixes);
        $pf = array_keys($prefixes);
        usort($sf, function($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        usort($pf, function($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        $batch = Plugin::getInstance()->iconify->batchIconSet($iconSet, $iconList);
        $total = count($batch);
        for ($i = 0; $i < $total; $i++) {
            $iconBody = Plugin::getInstance()->iconify->getIconsData($iconSet, $batch[$i]);
            foreach ($iconBody as $key => $value) {
                $suffix = Plugin::getInstance()->icons->getSuffixFromName($key, $sf);
                $prefix = Plugin::getInstance()->icons->getPrefixFromName($key, $pf);
                $icon = new Icon();
                $icon->name = $key;
                $icon->set = $iconSet;
                $icon->body = $value['body'];
                $icon->filename = Plugin::getInstance()->icons->iconFilename($key);
                $icon->prefixId = $prefix && $prefix !== '' ? $prefixes[$prefix] : null;
                $icon->suffixId = $suffix && $suffix !== '' ? $suffixes[$suffix] : null;
                Plugin::getInstance()->icons->saveIcon($icon);
            }
        }
    }
}