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

For discovery and menu fetching to stay current, run the scheduler (e.g. via cron or Windows Task Scheduler calling `php artisan schedule:run` every minute) alongside a queue worker.
