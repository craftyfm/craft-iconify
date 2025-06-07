<?php

namespace craftfm\iconify\jobs;

use Craft;
use craft\queue\BaseJob;
use craftfm\iconify\models\Icon;
use craftfm\iconify\Plugin;
use GuzzleHttp\Exception\GuzzleException;
use yii\base\ErrorException;
use yii\base\Exception;

class DownloadIconsQueueJob extends BaseJob
{

    public string $iconSet;

    /**
     * @throws Exception
     * @throws GuzzleException
     * @throws ErrorException
     */
    public function execute($queue): void
    {

        $iconList = Plugin::getInstance()->iconify->getIconList($this->iconSet);
        Plugin::getInstance()->icons->deleteIconSet($this->iconSet);
        $prefixes = [];
        $suffixes = [];
        foreach ($iconList['prefixes'] as $prefix => $label) {
            $id = Plugin::getInstance()->icons->saveIconAffix($this->iconSet, $prefix, $label, 'prefix');
            $prefixes[$prefix] = $id;
        }

        foreach ($iconList['suffixes'] as $suffix => $label) {
            $id = Plugin::getInstance()->icons->saveIconAffix($this->iconSet, $suffix, $label, 'suffix');
            $suffixes[$suffix] = $id;
        }
        $this->_saveIcons($iconList['icons'], $prefixes, $suffixes, $queue);

    }


    /**
     * @throws GuzzleException
     * @throws \yii\db\Exception
     * @throws Exception
     */
    private function _saveIcons(array $iconList, array $prefixes, array $suffixes, $queue): void
    {
        $sf = array_keys($suffixes);
        $pf = array_keys($prefixes);
        usort($sf, function($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        usort($pf, function($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        $batch = Plugin::getInstance()->iconify->batchIconSet($this->iconSet, $iconList);
        $total = count($batch);
        for ($i = 0; $i < $total; $i++) {
            $iconBody = Plugin::getInstance()->iconify->getIconsData($this->iconSet, $batch[$i]);
            foreach ($iconBody as $key => $value) {
                $suffix = Plugin::getInstance()->icons->getSuffixFromName($key, $sf);
                $prefix = Plugin::getInstance()->icons->getPrefixFromName($key, $pf);
                $icon = new Icon();
                $icon->name = $key;
                $icon->set = $this->iconSet;
                $icon->body = $value['body'];
                $icon->filename = Plugin::getInstance()->icons->iconFilename($key);
                $icon->prefixId = $prefix && $prefix !== '' ? $prefixes[$prefix] : null;
                $icon->suffixId = $suffix && $suffix !== '' ? $suffixes[$suffix] : null;
                Plugin::getInstance()->icons->saveIcon($icon);
            }
            $this->setProgress($queue, $i / $total);
        }
    }


    protected function defaultDescription(): string
    {
        return 'Downloading icons for ' . $this->iconSet;
    }

    protected function defaultLabel(): string
    {
        return 'Downloading icons from icon set';
    }
}