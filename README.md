# Lunch Breaker

A Laravel PWA that finds lunch spots near your office and helps colleagues coordinate eating together.

## What it does

- Set your office address plus a max walking distance/duration in **Office Settings**.
- Nearby restaurants, cafes, bakeries, and canteens are discovered automatically from OpenStreetMap (or Google Places) within that radius, with walking distance/duration calculated for each.
- The dashboard shows a daily lunch board: each nearby place, its menu for today (if entered), and who's RSVP'd "I'm in."
- Admins can manually add restaurants OSM is missing, and enter/edit daily menus, under **Manage Restaurants** / **Manage Menus**.
- Installable as a PWA (offline fallback page, add-to-home-screen).

## Stack

Laravel 11, Breeze (Blade + Alpine.js), MySQL, Vite + `vite-plugin-pwa`.

## Local setup

1. `composer install && npm install`
2. Copy `.env.example` to `.env`, set your DB credentials, run `php artisan key:generate`
3. Create the database, then `php artisan migrate`
4. `npm run build` (or `npm run dev` for local asset development)
5. `php artisan queue:work` — restaurant discovery, distance calculation, and menu fetching run as queued jobs, so a worker needs to be running for them to take effect
6. Serve the app (`php artisan serve`, or point Apache/Nginx at `public/`)

### Restaurant/geocoding drivers

Set in `.env`:

```env
GEO_DRIVER=osm       # osm (no API key) or google
PLACES_DRIVER=osm    # osm (no API key) or google
GOOGLE_PLACES_API_KEY=
```

The OSM driver uses Nominatim (geocoding), Overpass API (place search), and OSRM (walking distance) — all free, no API key required, suitable for local development. The Google driver requires a Places/Geocoding/Distance Matrix-enabled API key.

### Scheduled jobs

For discovery and menu fetching to stay current, run the scheduler (e.g. via cron or Windows Task Scheduler calling `php artisan schedule:run` every minute) alongside a queue worker. On Windows, register both as repeating Scheduled Tasks (every 1 minute):

```bash
php artisan schedule:run
php artisan queue:work --stop-when-empty --max-time=50
```

### Menu scrapers

Restaurants with `menu_source_type = scraper` get their menu fetched automatically:

- `menu_source_config = {"url": ..., "item_selector": ..., "name_selector": ..., "price_selector": ...}` — a plain HTML page, scraped via CSS selectors (`GenericHtmlScraperSource`).
- `menu_source_config = {"menu_page_url": ...}` or `{"pdf_url": ...}` — a weekly day-of-week grid menu, published as either a PDF or an image (`WeeklyGridMenuSource`). With `menu_page_url`, the current week's file is looked up by ISO week number on every fetch (since providers like dish.co publish a new file at a new URL each week); `pdf_url` is for a single unchanging file.

Image-based weekly menus need **Tesseract OCR** installed separately (not a Composer package):

1. Install Tesseract (e.g. `winget install UB-Mannheim.TesseractOCR`) and set `TESSERACT_BINARY` in `.env` to its path if not the Windows default.
2. Download a German language file (`deu.traineddata`, from [tesseract-ocr/tessdata_best](https://github.com/tesseract-ocr/tessdata_best)) into `storage/tessdata/` alongside a copy of `eng.traineddata` from the Tesseract install — the system install's own `tessdata` folder usually isn't writable without admin rights, so this app points `TESSERACT_TESSDATA_DIR` at a project-local copy instead.

OCR accuracy on stylized/decorative menu images is meaningfully lower than text extracted from a real PDF — structured item+price splitting has a stricter internal safety check that only accepts the split when it's unambiguous, so noisy OCR results commonly fall back to showing the full extracted text rather than risking confidently-wrong structured data.
