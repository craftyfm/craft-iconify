<?php

namespace craftfm\iconify\models;

use craft\base\Model;

class Icon extends Model
{
    public ?int $id = null;
    public string $name;
    public string $set;
    public string $filename;
    public ?string $prefix;
    public ?string $suffix;
    public string $body;

    public function rules(): array
    {
        return [
            [['name', 'set', 'svg'], 'required'],
            [['name', 'set'], 'string', 'max' => 255],
            ['path', 'string'],
            ['prefix', 'string'],
            ['suffix', 'string'],
        ];
    }
}