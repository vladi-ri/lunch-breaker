<?php

namespace App\Domain\Menus;

use App\Domain\Menus\Sources\GenericHtmlScraperSource;
use App\Domain\Menus\Sources\ManualMenuSource;
use App\Models\Restaurant;
use Illuminate\Contracts\Container\Container;

class MenuSourceResolver
{
    public function __construct(
        protected Container $container,
    ) {}

    public function resolve(Restaurant $restaurant): ?MenuSource
    {
        return match ($restaurant->menu_source_type) {
            'manual', 'none' => $this->container->make(ManualMenuSource::class),
            'scraper' => $this->resolveScraper($restaurant),
            default => null,
        };
    }

    protected function resolveScraper(Restaurant $restaurant): ?MenuSource
    {
        $config = $restaurant->menu_source_config ?? [];

        // A custom per-restaurant scraper class can be set via menu_source_config['class'].
        if (! empty($config['class']) && is_a($config['class'], MenuSource::class, true)) {
            return $this->container->make($config['class']);
        }

        return $this->container->make(GenericHtmlScraperSource::class);
    }
}
