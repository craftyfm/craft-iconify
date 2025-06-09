<?php

namespace craftfm\iconify\models;

use craft\base\Model;
use craftfm\iconify\Plugin;

class Icon extends Model
{
    public ?int $id = null;
    public string $name;
    public string $set;
    public string $filename;
    public ?string $prefixId;
    public ?string $suffixId;
    public string $body;

    public function getSvg(string $color = null, string $stroke = null): string
    {
        if (isset($this->body) && $this->body) {
            return Plugin::$plugin->icons->buildSvg($this->body, $color, $stroke);
        }
        return '';
    }

    public function rules(): array
    {
        return [
            [['name', 'set', 'svg'], 'required'],
            [['name', 'set'], 'string', 'max' => 255],
            ['path', 'string'],
        ];
    }
}