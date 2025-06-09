<?php

namespace craftfm\iconify\fields\Data;

use craft\base\Serializable;
use craftfm\iconify\Plugin;
use Twig\Markup;

class IconifyPickerData implements Serializable
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

    public function __toString(): string
    {
        return  Plugin::getInstance()->icons->getIconSvgMarkup($this->name, $this->set, $this->color, $this->strokeWidth);
    }

    public function getSvg(string $color = null, float $strokeWidth = null): Markup
    {
        return Plugin::getInstance()->icons->renderIcon($this->name, $this->set, $color ?: $this->color, $strokeWidth ?: $this->strokeWidth);
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

    public function getValue(): string
    {
        return $this->name;
    }
}