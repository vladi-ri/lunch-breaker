<?php

namespace App\Domain\Menus\Sources;

use App\Domain\Menus\MenuResult;
use App\Domain\Menus\MenuSource;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * Parses a weekly menu PDF laid out as a day-of-week grid (a common template
 * used by canteen ordering platforms such as dish.co): a header row of German
 * weekday names, followed by a block of "Kantine <price> Abholung <price>"
 * cells (row-major: every day for row 1, then every day for row 2, ...), then
 * a block of dish descriptions in the same row-major order.
 *
 * Configured via `menu_source_config`:
 * ['pdf_url' => 'https://.../Speisekarte.pdf']
 *
 * Structured per-item extraction only succeeds when the number of price cells
 * exactly matches the number of dish-text pieces split out (each dish is
 * expected to end in a parenthesized allergen note); if a real-world PDF
 * doesn't consistently mark every dish that way, this degrades gracefully —
 * the full extracted text is still saved as `raw_text` regardless, so nothing
 * is silently lost, it just isn't broken into clean priced line items.
 */
class PdfWeeklyGridMenuSource implements MenuSource
{
    protected const GERMAN_WEEKDAYS = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

    public function fetch(Restaurant $restaurant, CarbonImmutable $date): ?MenuResult
    {
        $config = $restaurant->menu_source_config ?? [];

        if (empty($config['pdf_url'])) {
            return null;
        }

        $text = $this->extractText($config['pdf_url']);

        if ($text === null) {
            return null;
        }

        $weekday = self::GERMAN_WEEKDAYS[$date->dayOfWeekIso - 1];
        $dayNames = $this->findDayHeader($text);

        // The canteen simply has nothing on days it doesn't list (e.g. weekends).
        if ($dayNames === null || ! in_array($weekday, $dayNames, true)) {
            return null;
        }

        $items = $this->extractItemsForDay($text, $dayNames, $weekday);

        return new MenuResult(items: $items, rawText: $text);
    }

    protected function extractText(string $url): ?string
    {
        $response = Http::get($url);

        if (! $response->ok()) {
            Log::warning('PDF menu download failed', ['url' => $url, 'status' => $response->status()]);

            return null;
        }

        try {
            return (new PdfParser)->parseContent($response->body())->getText();
        } catch (\Throwable $e) {
            Log::warning('PDF menu parse failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @return string[]|null the weekday names as they appear in the document, in order
     */
    protected function findDayHeader(string $text): ?array
    {
        $pattern = '/\b('.implode('|', self::GERMAN_WEEKDAYS).')\b/u';

        preg_match_all($pattern, $text, $matches);

        // The header line repeats each weekday exactly once, consecutively -
        // take the first run of unique consecutive day names as the header.
        $found = [];
        foreach ($matches[1] as $day) {
            if (in_array($day, $found, true)) {
                break;
            }
            $found[] = $day;
        }

        return count($found) >= 2 ? $found : null;
    }

    /**
     * @return array<int, array{name: string, description: ?string, price: ?float}>
     */
    protected function extractItemsForDay(string $text, array $dayNames, string $weekday): array
    {
        $numDays = count($dayNames);
        $dayIndex = array_search($weekday, $dayNames, true);

        preg_match_all('/(\d+[,.]\d{2})\s*€\s*(\d+[,.]\d{2})\s*€/u', $text, $priceMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

        if (empty($priceMatches) || count($priceMatches) % $numDays !== 0) {
            return [];
        }

        $numRows = intdiv(count($priceMatches), $numDays);

        // Dish descriptions are expected to appear after the price grid, one
        // per cell, each terminated by a "(...)" allergen note - split on that.
        $lastMatch = $priceMatches[array_key_last($priceMatches)][0];
        $dishBlock = substr($text, $lastMatch[1] + strlen($lastMatch[0]));
        $dishes = array_values(array_filter(array_map(
            fn (string $piece) => trim(preg_replace('/\s+/u', ' ', $piece)),
            preg_split('/\([^)]*\)\s*/u', $dishBlock),
        ), fn (string $piece) => $piece !== ''));

        if (count($dishes) !== count($priceMatches)) {
            // Can't confidently line dishes up with prices (e.g. a dish missing
            // its allergen note merges with its neighbour) - bail rather than guess.
            return [];
        }

        $items = [];

        for ($row = 0; $row < $numRows; $row++) {
            $index = $row * $numDays + $dayIndex;

            $items[] = [
                'name' => $dishes[$index],
                'description' => null,
                'price' => (float) str_replace(',', '.', $priceMatches[$index][1][0]),
            ];
        }

        return $items;
    }
}
