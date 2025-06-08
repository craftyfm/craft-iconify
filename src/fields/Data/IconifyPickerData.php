<?php

namespace craftfm\iconify\fields\Data;

use craft\base\Serializable;
use yii\base\BaseObject;

class IconifyPickerData  implements Serializable
{
    public ?string $name = null;
    public ?string $set = null;
    public ?string $color = null;
    public ?float $strokeWidth = null;

    public function __construct(string $name, string $set, string $color = null, float $strokeWidth = null)
    {
        $this->name = $name;
        $this->set = $set;
        $this->color = $color;
        $this->strokeWidth = $strokeWidth;
    }

    public function serialize(): array
    {
       return array_filter([
           'name' => $this->name,
           'set' => $this->set,
           'color' => $this->color,
           'strokeWidth' => $this->strokeWidth
       ]);
    }
}