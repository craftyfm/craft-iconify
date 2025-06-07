<?php

namespace craftfm\iconify\services;

use Craft;
use craft\base\Component;
use craft\helpers\FileHelper;
use craftfm\iconify\records\IconRecord;
use Twig\Markup;
use yii\base\ErrorException;
use yii\base\Exception;
use craftfm\iconify\models\Icon as IconModel;

class Icons extends Component
{

    /**
     * @param array $options
     * @return IconModel[]
     */
    public function getIconsModel(array $options = [], int $limit = null, int $offset = null): array
    {
        // Start a query on IconRecord
        $query = IconRecord::find();

        // Apply filtering options dynamically
        foreach ($options as $attribute => $value) {
            $query->andWhere([$attribute => $value]);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        // Fetch all matching records
        $records = $query->all();

        $models = [];

        // Convert each record to a model instance
        foreach ($records as $record) {
            $model = new IconModel();
            $model->id = $record->id;
            $model->name = $record->name;
            $model->set = $record->set;
            $model->filename = $record->filename;
            // assign other properties if any...

            $models[] = $model;
        }

        return $models;
    }


    public function buildSvg(string $body, string $color = null, float $stroke = null): string
    {
        if ($color) {
            $body = str_replace('currentColor', $color, $body);
        } else {
            $color = 'currentColor';
        }
        $strokeAtt = '';
        if ($stroke) {
            $strokeAtt = "stroke='$color' stroke-width='$stroke'";
        }
        return <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" $strokeAtt>
                $body
            </svg>
            SVG;
    }

    public function getIconSvg(string $icon, string $set, string $color = null, float $stroke = null): string
    {
        $path = $this->getIconSetDirectory($set);

        $file = $path . DIRECTORY_SEPARATOR . $icon . '.svgfrag';
        if (file_exists($file)) {
            $svgFrag = file_get_contents($file);
            return $this->buildSvg($svgFrag, $color, $stroke);
        }

        return  '';
    }

    public function getIcon(string $iconSet, string $icon, string $color = null, float $stroke = null): Markup
    {
        $path = $this->getIconSetDirectory($iconSet);

        $file = $path . DIRECTORY_SEPARATOR . $icon . '.svgfrag';
        if (file_exists($file)) {
            $svgFrag = file_get_contents($file);
            return new Markup($this->buildSvg($svgFrag, $color, $stroke), 'UTF-8');
        }

        return new Markup('', 'UTF-8');
    }


    /**
     * @throws \yii\db\Exception
     * @throws Exception
     */
    public function saveIcon(IconModel $icon): void
    {
        if (!isset($icon->filename)) {
            $icon->filename = $icon->name . '.svgfrag';
        }
        $folderPath = $this->getIconSetDirectory($icon->set);
        $filePath = $folderPath . DIRECTORY_SEPARATOR . $icon->filename;
        $this->_checkDirectory($folderPath);
        $this->saveIconBody($filePath, $icon->body);
        $record = new IconRecord();
        $record->name = $icon;
        $record->set = $icon->set;
        $record->filename = $icon->filename;
        $record->prefix = $icon->prefix;
        $record->suffix = $icon->suffix;
        if (!$record->save()) {
            throw new Exception(Craft::t('icons', 'Unable to save icon.'));
        }
        $icon->id = $record->id;

    }

    public function saveIconBody(string $path, string $iconBody): void
    {
        if (!file_put_contents($path, $iconBody)) {
            throw new Exception(Craft::t('icons', 'Unable to save icon.'));
        }
    }

    /**
     * @throws ErrorException
     */
    public function deleteIconSet(string $iconSet): void
    {
        $folderPath = $this->getIconSetDirectory($iconSet);
        if (is_dir($folderPath)) {
            FileHelper::removeDirectory($folderPath);
        }

        IconRecord::deleteAll(['set' => $iconSet]);
        $cache = Craft::$app->getCache();
        $keyListKey = sprintf('iconify-picker-options-list-html-%s-keys', $iconSet);
        $keyList = $cache->get($keyListKey) ?: [];

        foreach ($keyList as $key) {
            $cache->delete($key);
        }
        $cache->delete($keyListKey);
    }

    public function getIconSetDirectory(string $iconSet): string
    {
        return Craft::getAlias("@storage/iconify/icons/{$iconSet}");
    }
    /**
     */
    private function _checkDirectory(string $folderPath): void
    {
        if (!is_dir($folderPath)) {
            mkdir($folderPath, 0755, true);
        }
    }
}