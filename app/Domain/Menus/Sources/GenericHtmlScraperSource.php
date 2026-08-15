<?php

namespace App\Domain\Menus\Sources;

use App\Domain\Menus\MenuResult;
use App\Domain\Menus\MenuSource;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Scrapes a menu from `menu_source_config`, e.g.:
 * [
 *   'url' => 'https://restaurant.example/menu',
 *   'item_selector' => '.menu-item',
 *   'name_selector' => '.item-name',
 *   'description_selector' => '.item-description',
 *   'price_selector' => '.item-price',
 * ]
 */
class GenericHtmlScraperSource implements MenuSource
{
    public function fetch(Restaurant $restaurant, CarbonImmutable $date): ?MenuResult
    {
        $config = $restaurant->menu_source_config ?? [];

        if (empty($config['url']) || empty($config['item_selector'])) {
            Log::warning('Generic scraper missing url/item_selector', ['restaurant_id' => $restaurant->id]);

            return null;
        }

        $response = Http::get($config['url']);

        if (! $response->ok()) {
            Log::warning('Generic scraper fetch failed', ['restaurant_id' => $restaurant->id, 'status' => $response->status()]);

            return null;
        }

        $crawler = new Crawler($response->body());
        $items = [];

        $crawler->filter($config['item_selector'])->each(function (Crawler $node) use ($config, &$items) {
            $name = $this->extract($node, $config['name_selector'] ?? null);

            if ($name === null) {
                return;
            }

            $items[] = [
                'name' => $name,
                'description' => $this->extract($node, $config['description_selector'] ?? null),
                'price' => $this->extractPrice($node, $config['price_selector'] ?? null),
            ];
        });

        if (empty($items)) {
            return null;
        }

        return new MenuResult(items: $items);
    }

    protected function extract(Crawler $node, ?string $selector): ?string
    {
        if ($selector === null) {
            return null;
        }

        $matches = $node->filter($selector);

        return $matches->count() > 0 ? trim($matches->first()->text()) : null;
    }

    protected function extractPrice(Crawler $node, ?string $selector): ?float
    {
        $text = $this->extract($node, $selector);

        if ($text === null) {
            return null;
        }

        preg_match('/[\d]+[.,]?[\d]*/', $text, $matches);

        return isset($matches[0]) ? (float) str_replace(',', '.', $matches[0]) : null;
    }
}
