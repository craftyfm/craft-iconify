<?php

namespace craftfm\iconify\records;
use craft\db\ActiveRecord;

/**
 * @property-read  int $id
 * @property string $name
 * @property string $slug
 * @property string $iconSet
 * @property string $type
 */
class AffixRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%iconify_affixes}}';
    }
}