<?php

namespace craftfm\iconify\records;
use craft\db\ActiveRecord;

/**
 * @property-read  int $id
 * @property string $name
 * @property string $set
 * @property string $filename
 * @property string $prefix
 * @property string $suffix
 */
class IconRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%iconify_icons}}';
    }
}