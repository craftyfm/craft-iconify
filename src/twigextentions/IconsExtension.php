<?php

namespace craftfm\iconify\twigextentions;

use Craft;
use craftfm\iconify\Plugin;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;

class IconsExtension extends  AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('iconify', [$this, 'iconifyFilter']),
        ];
    }
    public function getFunctions(): array
    {
        return [
          new TwigFunction('iconify', [Plugin::getInstance()->icons, 'getIcon']),
        ];
    }

    public function iconifyFilter(string $icon): Markup {
        $parts = explode(':', $icon);
        if (count($parts) < 2) {
            return new Markup('', 'UTF-8');
        }

        return Plugin::getInstance()->icons->getIcon($parts[0], $parts[1]);
    }


}