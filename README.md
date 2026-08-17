# Lunch Breaker

A Laravel PWA that finds lunch spots near your office and helps colleagues coordinate eating together.

## What it does

- Set your office address plus a max walking distance/duration in **Office Settings**.
- Nearby restaurants, cafes, bakeries, and canteens are discovered automatically from OpenStreetMap (or Google Places) within that radius, with walking distance/duration calculated for each.
- The dashboard shows a daily lunch board: each nearby place, its menu for today (if entered), and who's RSVP'd "I'm in."
- Admins can manually add restaurants OSM is missing, and enter/edit daily menus, under **Manage Restaurants** / **Manage Menus**.
- Installable as a PWA (offline fallback page, add-to-home-screen).

## Roadmap

- **Cluster restaurants by location.** Discovery now matches OSM `way`/`relation` elements as well as nodes (needed to catch shops mapped as building/unit outlines, e.g. inside train stations), which means places that share one building or complex — a station food court, a shopping mall — show up as several separate board entries right next to each other. Group results that sit within a few meters of each other into a single clustered card instead of listing each one individually.
- **Persistently show today's chosen spot.** Once a restaurant/occasion for the day is settled, surface it permanently somewhere always visible — e.g. next to the dashboard header, or a footnote — instead of it only being visible by scanning RSVPs on the board.

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

## Production deployment (Raspberry Pi + Docker + Cloudflare Tunnel)

Same setup as this app's siblings (`house-pulse`, `termin-routenplaner`): Docker Compose stack on a Raspberry Pi, published through an existing Cloudflare Tunnel, kept up to date by a cron-polled `deploy.sh` rather than manual `git pull`s.

```text
Internet → Cloudflare → Cloudflare Tunnel → nginx :8082 → PHP-FPM (app) → MariaDB (db)
                                                          ↳ scheduler (schedule:work)
                                                          ↳ queue (queue:work)
```

Assumes the Pi already has Docker, a GitHub SSH key, and a Cloudflare Tunnel running (as set up for the other apps) — this only covers what's specific to lunch-breaker.

### 1. Clone and configure

```bash
cd ~
git clone git@github.com:vladi-ri/lunch-breaker.git
cd lunch-breaker
cp .env.example .env
```

Edit `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lunch-breaker.vladislav-riemer.de
APP_NAME="Lunch Breaker"

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=lunch_breaker
DB_USERNAME=app
DB_PASSWORD=app

QUEUE_CONNECTION=database

GEO_DRIVER=osm
PLACES_DRIVER=osm

# Leave both blank - `apt install tesseract-ocr tesseract-ocr-deu` (already
# in the Dockerfile) puts everything on Tesseract's own default search path.
TESSERACT_BINARY=
TESSERACT_TESSDATA_DIR=
```

`DB_HOST=db` (the Compose service name), not `127.0.0.1` — inside Docker, `127.0.0.1` means "this container."

### 2. Build and start

```bash
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker run --rm -v "$(pwd)":/app -w /app node:20 sh -c "npm install && npm run build"
docker compose exec app sh -c \
  "chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache"
```

Verify locally on the Pi before touching Cloudflare:

```bash
curl -I http://localhost:8082/
```

### 3. Cloudflare Tunnel: add a public hostname

The tunnel itself already exists (shared by every app on this Pi) — this only adds one more route to it.

```text
Cloudflare Dashboard → Zero Trust → Networks → Tunnels → (the existing tunnel) → Public Hostname → Add
  Subdomain: lunch-breaker
  Domain:    vladislav-riemer.de
  Service:   HTTP → localhost:8082
```

Cloudflare creates the DNS record automatically. `https://lunch-breaker.vladislav-riemer.de` should be live within a minute or two, terminating TLS at Cloudflare's edge (the origin stays plain HTTP on the LAN, same as the other apps).

### 4. Auto-deploy on push

```bash
crontab -e
```

Add:

```cron
*/5 * * * * /home/<user>/lunch-breaker/deploy.sh >> /home/<user>/lunch-breaker/deploy.log 2>&1
```

From here, pushing to `main` is the entire deploy process — `deploy.sh` notices the new commit, pulls, reinstalls dependencies, rebuilds assets, migrates, and recreates the containers on its own.

### 5. PWA verification

Since this is served over real HTTPS on a stable hostname (unlike local dev), installability should just work: open `https://lunch-breaker.vladislav-riemer.de` in Chrome, check DevTools → Application → Manifest/Service Workers for no errors, and confirm the install prompt appears.

### Notes / deviations from the sibling apps

- **`queue` container**: lunch-breaker dispatches queued jobs (restaurant discovery, distance calculation, menu fetching) that house-pulse/termin-routenplaner don't have, so it gets its own `queue:work` container alongside `scheduler`, rather than relying on schedule:work alone.
- **`db` port not published to the host**: only `web` needs a host port (`8082:80`); the database is reachable internally as `db:3306` for the app/scheduler/queue containers, which avoids colliding with any other app's `3306:3306` mapping on the same Pi.
- **Port `8082`**: `8080` is termin-routenplaner, `8081` is house-pulse.
