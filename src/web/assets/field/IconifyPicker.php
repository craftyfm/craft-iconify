<?php

namespace craftfm\iconify\web\assets\field;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class IconifyPicker extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = "@craftyfm/iconify/web/assets/field/src/";

        $this->depends = [
            CpAsset::class, // includes Craft CP styles & JS
        ];

        $this->js = [
            'js/IconPicker.js',
            'js/IconPickerCustomize.js',
        ];

        $this->css = [
            'css/iconify-picker.css',
        ];

        parent::init();
    }
}