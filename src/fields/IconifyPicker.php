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
use craftfm\iconify\Plugin;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Schema;
use \craftfm\iconify\web\assets\field\IconifyPicker as HugeIconPickerAsset;

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
        return 'string|null';
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): string
    {
        return Schema::TYPE_STRING;
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
        return ($value || $value === '0') ? $value : null;
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
        Craft::$app->getView()->registerAssetBundle(HugeIconPickerAsset::class);
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
            "defaultSet" => $iconSets ? array_key_first($iconSets): ""
        ];
        return Craft::$app->getView()->renderTemplate('iconify/_fields/icon-picker.twig', $config, View::TEMPLATE_MODE_CP);

    }

    /**
     * @inheritdoc
     */
    public function getStaticHtml(mixed $value, ElementInterface $element): string
    {
        return Cp::iconPickerHtml([
            'static' => true,
            'value' => $value,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        return $value ? Html::tag('div', Cp::iconSvg($value), ['class' => 'cp-icon']) : '';
    }

    /**
     * @inheritdoc
     */
    public function previewPlaceholderHtml(mixed $value, ?ElementInterface $element): string
    {
        if (!$value) {
            $value = 'info';
        }

        return $this->getPreviewHtml($value, $element ?? new Entry());
    }

    /**
     * @inheritdoc
     */
    public function getThumbHtml(mixed $value, ElementInterface $element, int $size): ?string
    {
        return $value ? Html::tag('div', Cp::iconSvg($value), ['class' => 'cp-icon']) : null;
    }
}
