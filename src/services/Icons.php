<?php

namespace craftfm\iconify\services;

use Craft;
use craft\base\Component;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craftfm\iconify\Plugin;
use craftfm\iconify\records\AffixRecord;
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
    public function getIconsModel(array $options = [], int $limit = null, int $offset = null, string $search = null): array
    {
        $settings = Plugin::getInstance()->getSettings();
        // Start a query on IconRecord
        $query = IconRecord::find();

        // Apply filtering options dynamically
        foreach ($options as $attribute => $value) {
            if ($attribute === 'affixId') {
                $query->andWhere(
                  ['OR', ['suffixId' => $value], ['prefixId' => $value]]
                );
            } else {
                $query->andWhere([$attribute => $value]);
            }
        }

        if ($search) {
            $query->andWhere(['like', 'name', $search]);
        }
        if ($limit !== null) {
            $query->limit($limit);
        }

        if ($offset !== null) {
            $query->offset($offset);
        }

        $query->orderBy(['name' => SORT_ASC]);
        // Fetch all matching records
        $records = $query->all();

        $models = [];

        // Convert each record to a model instance
        /**
         * @var IconRecord $record
         */
        foreach ($records as $record) {
            $model = new IconModel();
            $model->id = $record->id;
            $model->name = $record->name;
            $model->set = $record->set;
            $model->filename = $record->filename;
            if ($settings->storage === $settings::LOCAL_STORAGE) {
                $this->_getSvgBodyFromLocalStorage($record->name, $record->set);
            } else {
                $model->body = $record->body;
            }
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

    public function getIconSvgMarkup(string $icon, string $set, string $color = null, float $stroke = null): string
    {
        $settings = Plugin::getInstance()->getSettings();
        if ($settings->storage === $settings::LOCAL_STORAGE) {
            return $this->buildSvg($this->_getSvgBodyFromLocalStorage($icon, $set), $color, $stroke);
        }
        return $this->buildSvg($this->_getSvgBodyFromDatabase($icon, $set), $color, $stroke);

    }

    public function renderIcon(string $icon, string $set, string $color = null, float $stroke = null): Markup
    {
        $svg = $this->getIconSvgMarkup($icon, $set, $color, $stroke);
        return new Markup($svg, 'UTF-8');
    }

    /**
     * @throws \yii\db\Exception
     * @throws Exception
     */
    public function saveIcon(IconModel $icon): void
    {
        $settings = Plugin::getInstance()->getSettings();
        if ($settings->storage === $settings::LOCAL_STORAGE) {
            $folderPath = $this->_getIconSetDirectory($icon->set);
            $filePath = $folderPath . DIRECTORY_SEPARATOR . $icon->filename;
            $this->_checkDirectory($folderPath);
            $this->saveIconBody($filePath, $icon->body);
        }
        $this->saveIconRecord($icon);
    }

    /**
     * @throws Exception
     * @throws \yii\db\Exception
     */
    public function saveIconRecord(IconModel $icon): void
    {
        $settings = Plugin::getInstance()->getSettings();
        $record = IconRecord::find()
            ->where([
                'name' => $icon->name,
                'set' => $icon->set,
            ])
            ->one();
        if (!$record) {
            // Create a new record if it doesn't exist
            $record = new IconRecord();
        }

        // Set or update the fields
        $record->name = $icon->name;
        $record->set = $icon->set;
        if ($settings->storage === $settings::DATABASE_STORAGE) {
            $record->body = $icon->body;
            $record->filename = '';
        } else {
            $record->body = '';
            $record->filename = $icon->filename;
        }
        $record->prefixId = $icon->prefixId;
        $record->suffixId = $icon->suffixId;

        if (!$record->save()) {
            throw new Exception(Craft::t('icons', 'Unable to save icon.'));
        }

        // Set the ID back on the $icon object
        $icon->id = $record->id;
    }

    /**
     * @throws Exception
     */
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
        $folderPath = $this->_getIconSetDirectory($iconSet);
        if (is_dir($folderPath)) {
            FileHelper::removeDirectory($folderPath);
        }

        $this->deleteIconSetAffixes($iconSet);
        IconRecord::deleteAll(['set' => $iconSet]);
        $this->clearIconCache($iconSet);

    }


    /**
     * @throws \yii\db\Exception
     */
    public function saveIconAffix(string $iconSet, string $affix, string $label, string $type): ?int
    {
        if ($affix === '') {
//            $affix = StringHelper::slugify($label);
            return null;
        }
        $record = new AffixRecord();
        $record->iconSet = $iconSet;
        $record->slug = $affix;
        $record->name = $label;
        $record->type = $type;
        $record->save();
        return $record->id;
    }

    public function getPrefixFromName(string $name, array $prefixes): ?string
    {
        foreach ($prefixes as $prefix ) {
            if ($prefix === '') {
                return null;
            }
            if (str_starts_with($name, $prefix)) {
                return $prefix;
            }
        }
        return null;
    }

    public function getSuffixFromName(string $name, array $suffixes): ?string
    {
        foreach ($suffixes as $suffix ) {
            if ($suffix === '') {
                return null; // Default if no suffix matches
            }
            if (str_ends_with($name, $suffix)) {
                return $suffix;
            }
        }
        return null;
    }

    public function getIconSetAffixes(string $iconSet): array
    {
        return AffixRecord::findAll(['iconSet' => $iconSet]);
    }
    public function iconFilename(string $iconName): string
    {
        return $iconName . '.svgfrag';
    }



    public function deleteIconSetAffixes(string $iconSet): void
    {

        AffixRecord::deleteAll(['iconSet' => $iconSet]);
    }

    public function clearIconCache(string $iconSet): void
    {
        $cache = Craft::$app->getCache();
        $keyListKey = sprintf('iconify-picker-options-list-html-%s-keys', $iconSet);
        $keyList = $cache->get($keyListKey) ?: [];

        foreach ($keyList as $key) {
            $cache->delete($key);
        }
        $cache->delete($keyListKey);
    }

    public function getExampleIcon(): Markup
    {
        $path = Craft::getAlias('@craftyfm/iconify/resources/icons/ico.svg');
        $content = file_get_contents($path);
        return new Markup($content, 'UTF-8');
    }
    private function _getIconSetDirectory(string $iconSet): string
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

    private function _getSvgBodyFromDatabase(string $icon, string $set): string
    {
        $record = IconRecord::findOne(['name' => $icon, 'set' => $set]);
        if ($record) {
            return $record->body;
        }
        return '';
    }
    private function _getSvgBodyFromLocalStorage(string $icon, string $set): string
    {
        $path = $this->_getIconSetDirectory($set);
        $file = $path . DIRECTORY_SEPARATOR . $icon . '.svgfrag';
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        return  '';
    }
}