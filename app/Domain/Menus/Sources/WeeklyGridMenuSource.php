<?php

namespace App\Domain\Menus\Sources;

use App\Domain\Menus\MenuResult;
use App\Domain\Menus\MenuSource;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser as PdfParser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Process\Process;

/**
 * Parses a weekly menu laid out as a day-of-week grid (a common template used
 * by canteen ordering platforms such as dish.co): a header row of German
 * weekday names, followed by a block of "Kantine <price> Abholung <price>"
 * cells (row-major: every day for row 1, then every day for row 2, ...), then
 * a block of dish descriptions in the same row-major order.
 *
 * The weekly file itself is sometimes a PDF and sometimes an image (this
 * provider publishes both across different weeks) - the content type is
 * detected per fetch and routed to the matching text extractor (PDF text
 * layer vs Tesseract OCR); everything downstream operates on plain text
 * either way.
 *
 * Configured via `menu_source_config`, one of:
 * ['menu_page_url' => 'https://.../'] - a stable page listing each week's menu
 *   download (e.g. "Speisekarte KW33" -> some-file.pdf/png); the link matching
 *   the requested date's ISO week number is resolved fresh on every fetch,
 *   since providers like dish.co publish a new file at a new URL every week.
 * ['pdf_url' => 'https://.../Speisekarte.pdf'] - a single, unchanging file
 *   (used only if menu_page_url isn't set).
 *
 * Structured per-item extraction only succeeds when the number of price cells
 * exactly matches the number of dish-text pieces split out (each dish is
 * expected to end in a parenthesized allergen note); if the source doesn't
 * consistently mark every dish that way - or OCR noise breaks the pattern -
 * this degrades gracefully: the full extracted text is still saved as
 * `raw_text`, so nothing is silently lost, it just isn't broken into clean
 * priced line items.
 */
class WeeklyGridMenuSource implements MenuSource
{
    protected const GERMAN_WEEKDAYS = ['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'];

    protected const IMAGE_EXTENSIONS = ['png', 'jpg', 'jpeg'];

    public function fetch(Restaurant $restaurant, CarbonImmutable $date): ?MenuResult
    {
        $config = $restaurant->menu_source_config ?? [];

        $fileUrl = ! empty($config['menu_page_url'])
            ? $this->resolveCurrentWeekFileUrl($config['menu_page_url'], $date)
            : ($config['pdf_url'] ?? null);

        if ($fileUrl === null) {
            return null;
        }

        $text = $this->extractText($fileUrl);

        if ($text === null || trim($text) === '') {
            return null;
        }

        // Sanity check against the document's own stated date range (e.g.
        // "10.08.-14.08.2026"), so a stale or mismatched file - whether from a
        // hardcoded url or a week-number match gone wrong - never gets
        // silently presented as if it were this date's menu.
        if (! $this->dateFallsWithinStatedRange($text, $date)) {
            Log::warning('Weekly menu date range does not include requested date', ['url' => $fileUrl, 'date' => $date->toDateString()]);

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

    /**
     * Finds the file link on a landing page whose label names the requested
     * date's ISO week (e.g. "Speisekarte KW33"), regardless of whether that
     * week happens to be published as a PDF or an image.
     */
    protected function resolveCurrentWeekFileUrl(string $pageUrl, CarbonImmutable $date): ?string
    {
        $response = Http::get($pageUrl);

        if (! $response->ok()) {
            Log::warning('Menu page download failed', ['url' => $pageUrl, 'status' => $response->status()]);

            return null;
        }

        $isoWeek = $date->isoWeek;
        $crawler = new Crawler($response->body());
        $matchedUrl = null;

        $crawler->filter('.menu-downloads')->each(function (Crawler $node) use ($isoWeek, &$matchedUrl) {
            if ($matchedUrl !== null) {
                return;
            }

            $title = $node->filter('.menu-title')->count() > 0 ? $node->filter('.menu-title')->first()->text() : '';

            if (! preg_match('/KW\s*0*'.$isoWeek.'\b/u', $title)) {
                return;
            }

            $link = $node->filter('a[href]');

            if ($link->count() > 0) {
                $matchedUrl = $link->first()->attr('href');
            }
        });

        return $matchedUrl;
    }

    protected function extractText(string $url): ?string
    {
        $response = Http::get($url);

        if (! $response->ok()) {
            Log::warning('Menu file download failed', ['url' => $url, 'status' => $response->status()]);

            return null;
        }

        $body = $response->body();
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        $isPdf = $extension === 'pdf' || str_starts_with($body, '%PDF');
        $isImage = in_array($extension, self::IMAGE_EXTENSIONS, true) || $this->looksLikeImage($body);

        if ($isPdf) {
            return $this->extractTextFromPdf($body, $url);
        }

        if ($isImage) {
            return $this->extractTextFromImage($body, $url);
        }

        Log::warning('Weekly menu file is neither a recognizable PDF nor image', ['url' => $url]);

        return null;
    }

    protected function looksLikeImage(string $body): bool
    {
        $signatures = [
            "\x89PNG\r\n\x1a\n", // PNG
            "\xFF\xD8\xFF",      // JPEG
        ];

        foreach ($signatures as $signature) {
            if (str_starts_with($body, $signature)) {
                return true;
            }
        }

        return false;
    }

    protected function extractTextFromPdf(string $body, string $url): ?string
    {
        try {
            return (new PdfParser)->parseContent($body)->getText();
        } catch (\Throwable $e) {
            Log::warning('PDF menu parse failed', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }

    protected function extractTextFromImage(string $body, string $url): ?string
    {
        $tesseractBinary = config('services.tesseract.binary');
        $tessdataDir = config('services.tesseract.tessdata_dir');

        if (empty($tesseractBinary) || ! is_file($tesseractBinary)) {
            Log::warning('Tesseract OCR is not configured/installed - cannot read image-based menu', ['url' => $url]);

            return null;
        }

        $tmpDir = sys_get_temp_dir();
        $imagePath = tempnam($tmpDir, 'menu_img_').'.'.($this->detectImageExtension($body) ?? 'png');
        $outputBase = tempnam($tmpDir, 'menu_ocr_');

        try {
            file_put_contents($imagePath, $body);

            $process = new Process([
                $tesseractBinary,
                $imagePath,
                $outputBase,
                '-l', 'deu',
            ], env: $tessdataDir ? ['TESSDATA_PREFIX' => $tessdataDir] : []);

            $process->setTimeout(60);
            $process->run();

            if (! $process->isSuccessful()) {
                Log::warning('Tesseract OCR failed', ['url' => $url, 'error' => $process->getErrorOutput()]);

                return null;
            }

            return file_exists("{$outputBase}.txt") ? file_get_contents("{$outputBase}.txt") : null;
        } finally {
            @unlink($imagePath);
            @unlink("{$outputBase}.txt");
            @unlink($outputBase);
        }
    }

    protected function detectImageExtension(string $body): ?string
    {
        if (str_starts_with($body, "\x89PNG\r\n\x1a\n")) {
            return 'png';
        }

        if (str_starts_with($body, "\xFF\xD8\xFF")) {
            return 'jpg';
        }

        return null;
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

        // The decimal separator is optional: OCR on the image-based weeks
        // frequently drops the comma (e.g. "7,80€" comes out as "780€") - in
        // that case the trailing two digits are still the cents.
        preg_match_all('/(\d{1,3}(?:[,.]\d{2})?)\s*€\s*(\d{1,3}(?:[,.]\d{2})?)\s*€/u', $text, $priceMatches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

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
            // its allergen note merges with its neighbour, or OCR noise broke
            // the pattern) - bail rather than guess.
            return [];
        }

        $items = [];

        for ($row = 0; $row < $numRows; $row++) {
            $index = $row * $numDays + $dayIndex;

            $items[] = [
                'name' => $dishes[$index],
                'description' => null,
                'price' => $this->normalizePrice($priceMatches[$index][1][0]),
            ];
        }

        return $items;
    }

    protected function normalizePrice(string $raw): float
    {
        if (preg_match('/[,.]/', $raw)) {
            return (float) str_replace(',', '.', $raw);
        }

        // No separator (OCR dropped it) - the trailing two digits are cents.
        $digits = str_pad($raw, 3, '0', STR_PAD_LEFT);

        return ((float) substr($digits, 0, -2)) + ((float) substr($digits, -2) / 100);
    }

    protected function dateFallsWithinStatedRange(string $text, CarbonImmutable $date): bool
    {
        // e.g. "10.08.-14.08.2026" - a from/to day.month pair sharing one trailing year.
        if (! preg_match('/(\d{1,2})\.(\d{1,2})\.-(\d{1,2})\.(\d{1,2})\.(\d{4})/u', $text, $m)) {
            // No recognizable range in the document - don't block on something we can't verify.
            return true;
        }

        $from = CarbonImmutable::create((int) $m[5], (int) $m[2], (int) $m[1]);
        $to = CarbonImmutable::create((int) $m[5], (int) $m[4], (int) $m[3]);

        return $date->betweenIncluded($from, $to);
    }
}
