<?php

namespace craftfm\iconify\utilities;

use Craft;
use craft\base\Utility;
use craftfm\iconify\Plugin;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

class DownloadIconsUtility extends Utility
{

    public static function id(): string
    {
        return 'download-icons';
    }

    /**
     * @throws SyntaxError
     * @throws Exception
     * @throws RuntimeError
     * @throws LoaderError
     * @throws \Exception
     */
    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        $settings = Plugin::getInstance()->getSettings();
        $iconSets = Plugin::getInstance()->iconify->getIconSets($settings->iconSets);
        $options = [
         '*' => 'All'
        ];
        foreach ($iconSets as $key => $value) {
            $options[$key] = $value['name'];
        }
        $params = [
          'iconSets' => $options,
        ];
        return $view->renderTemplate('iconify/_utilities/download', $params);
    }

    public static function displayName(): string
    {
        return "Iconify";
    }
}