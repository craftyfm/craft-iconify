<?php

namespace craftfm\iconify\models;

use Craft;
use craft\base\Model;

/**
 * Huge Icon Library settings
 */
class Settings extends Model
{
    public array $iconSets = [];

    public function rules(): array
    {
        return [
            [['iconSets'], 'safe'], // Or 'validate' as an array depending on need
        ];
    }
}
