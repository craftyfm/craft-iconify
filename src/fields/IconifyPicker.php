<?php

namespace craftfm\iconify\fields;

use Craft;
use craft\base\CrossSiteCopyableFieldInterface;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\InlineEditableFieldInterface;
use craft\base\MergeableFieldInterface;
use craft\base\ThumbableFieldInterface;
use craft\elements\Entry;
use craft\helpers\Cp;
use craft\helpers\Html;
use craft\web\twig\TemplateLoaderException;
use craft\web\View;
use craftfm\iconify\fields\Data\IconifyPickerData;
use craftfm\iconify\Plugin;
use craftfm\iconify\services\Iconify;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Schema;
use \craftfm\iconify\web\assets\field\IconifyPicker as IconifyPickerAsset;

/**
 * Icon represents an icon picker field.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
class IconifyPicker extends Field implements InlineEditableFieldInterface, ThumbableFieldInterface, MergeableFieldInterface, CrossSiteCopyableFieldInterface
{

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('iconify', 'Iconify Picker');
    }

    /**
     * @inheritdoc
     */
    public static function icon(): string
    {
        return 'icons';
    }

    /**
     * @inheritdoc
     */
    public static function phpType(): string
    {
        return sprintf('\\%s|null', IconifyPickerData::class);
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): array
    {
        return [
            'value' => Schema::TYPE_STRING,
            'name' => Schema::TYPE_STRING,
            'set' => Schema::TYPE_STRING,
            'color' => Schema::TYPE_STRING,
            'strokeWidth' => Schema::TYPE_FLOAT,
        ];
    }


    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
    }


    /**
     * @inheritdoc
     */
    public function normalizeValue(mixed $value, ?ElementInterface $element): mixed
    {
        if ($value instanceof IconifyPickerData) {
            return $value;
        }

        if(!is_array($value)) {
            return null;
        }

        if (!isset($value['set']) || !isset($value['name'])) {
            return null;
        }
        $name = $value['name'];
        $set = $value['set'];
        $color = $value['color'] ?? null;
        $strokeWidth = isset($value['strokeWidth']) ? floatval($value['strokeWidth']) : null;
        return new IconifyPickerData($name, $set, $color, $strokeWidth);
    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     * @throws \Exception
     */
    protected function inputHtml(mixed $value, ?ElementInterface $element, bool $inline): string
    {
        Craft::$app->getView()->registerAssetBundle(IconifyPickerAsset::class);
        $settings = Plugin::getInstance()->getSettings();
        $iconSetList = Plugin::getInstance()->iconify->getIconSets($settings->iconSets);
        $iconSets = [];
        foreach ($settings->iconSets as $key) {
            $iconSets[$key] = $iconSetList[$key]['name'] ?? $key;
        }
        $config = [
            'id' => $this->getInputId(),
            'describedBy' => $this->describedBy,
            'name' => $this->handle,
            'value' => $value,
            'iconSets' => $iconSets,
            "defaultSet" => $value ? $value->set: ""
        ];
        return Craft::$app->getView()->renderTemplate('iconify/_fields/icon-picker.twig', $config, View::TEMPLATE_MODE_CP);

    }

    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     * @throws \Exception
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }
        return $this->_renderStaticHtml($value);
    }


    /**
     * @inheritdoc
     * @throws InvalidConfigException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     * @throws \Exception
     */
    public function getThumbHtml(mixed $value, ElementInterface $element, int $size): ?string
    {
        if (!$value) {
            return '';
        }
        return $this->_renderStaticHtml($value);
    }

    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        return $this->_renderStaticHtml($value);
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
       if (!$value) {
           return '';
       }
        return $this->_renderStaticHtml($value);
    }


    private function _renderStaticHtml(mixed $value): string
    {
        Craft::$app->getView()->registerCss("
            .craftyfm-iconify-icon {
                border: 1px solid grey;
                padding: 2px;
                background: #eee;
                height: 2em;
                width: 2em;
                margin: 2px;
            }
        ");

        if (!$value) {
            $value = Plugin::getInstance()->icons->getExampleIcon();

        }
        return $value ? Html::tag('div', $value, ["class" => "craftyfm-iconify-icon"]) : "";
    }
}
