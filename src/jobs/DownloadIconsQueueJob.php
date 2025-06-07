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
        $icons = Plugin::getInstance()->iconify->getIconList($this->iconSet);
        $batch = Plugin::getInstance()->iconify->batchIconSet($this->iconSet, $icons);
        Plugin::getInstance()->icons->deleteIconSet($this->iconSet);
        $total = count($batch);
        for ($i = 0; $i < $total; $i++) {
            $iconBody = Plugin::getInstance()->iconify->getIconsData($this->iconSet, array_keys($batch[$i]));
            foreach ($iconBody as $key => $value) {
                $icon = new Icon();
                $icon->name = $key;
                $icon->set = $this->iconSet;
                $icon->body = $value['body'];
                $icon->prefix = $batch[$i][$key]['prefix'] ?? null;
                $icon->suffix = $batch[$i][$key]['suffix'] ?? null;
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