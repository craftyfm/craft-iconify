<?php

namespace craftfm\iconify\models;

use Craft;
use craft\base\Model;

/**
 * Huge Icon Library settings
 */
class Settings extends Model
{
    public const LOCAL_STORAGE = 'local';
    public const DATABASE_STORAGE = 'database';

    public string $storage = self::LOCAL_STORAGE;

    public array $iconSets = [];

    public function rules(): array
    {
        return [
            [['iconSets'], 'safe'], // Or 'validate' as an array depending on need
        ];
    }
}
