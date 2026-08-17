<?php

namespace App\Domain\Places\Drivers;

use App\Domain\Places\FindsNearbyPlaces;
use App\Domain\Places\PlaceResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OsmDriver implements FindsNearbyPlaces
{
    // amenity=* covers places you sit down and order (incl. workplace/school canteens).
    protected const AMENITIES = ['restaurant', 'cafe', 'fast_food', 'food_court', 'canteen'];

    // Bakeries/butchers/supermarkets are a shop=* in OSM, not an amenity —
    // amenity=bakery isn't a real tag, so it never matched anything before this
    // was split out. German butchers (Fleischerei) commonly sell hot snacks/lunch
    // items too, same as bakeries, and supermarkets often have a deli/salad bar.
    // greengrocer/convenience/deli cover fruit stands and station kiosks.
    protected const SHOPS = ['bakery', 'butcher', 'supermarket', 'greengrocer', 'convenience', 'deli'];

    public function __construct(
        protected string $overpassUrl = 'https://overpass-api.de/api/interpreter',
        protected string $userAgent = 'LunchBreakerApp/1.0 (+http://lunch-breaker.test)',
    ) {}

    public function nearby(float $lat, float $lng, int $radiusMeters): array
    {
        $amenityFilter = implode('|', self::AMENITIES);
        $shopFilter = implode('|', self::SHOPS);

        // nwr (not just node) matters here: shops inside larger complexes like
        // train stations or malls are frequently mapped as way outlines (room/unit
        // footprints), not standalone point nodes - node-only search silently
        // missed those. "out center" adds a synthesized lat/lon for way/relation
        // results (nodes already have one) so every element can be mapped the
        // same way below.
        $query = <<<OVERPASS
            [out:json][timeout:25];
            (
              nwr["amenity"~"^({$amenityFilter})$"](around:{$radiusMeters},{$lat},{$lng});
              nwr["shop"~"^({$shopFilter})$"](around:{$radiusMeters},{$lat},{$lng});
            );
            out center;
            OVERPASS;

        $response = Http::withHeaders(['User-Agent' => $this->userAgent])
            ->asForm()
            ->post($this->overpassUrl, ['data' => $query]);

        if (! $response->ok()) {
            // Must not return an empty array here: the caller (DiscoverRestaurantsJob)
            // reconciles its results by deactivating anything it didn't find, so a
            // silently-empty result on a transient failure (Overpass returns 504
            // under load fairly often) would read as "nothing nearby anymore" and
            // wipe out every previously-active restaurant. Throwing instead lets the
            // job fail and retry via the queue's normal retry mechanism.
            Log::warning('Overpass nearby search failed', ['status' => $response->status()]);

            throw new \RuntimeException("Overpass nearby search failed with status {$response->status()}");
        }

        $elements = $response->json('elements', []);

        return collect($elements)
            ->filter(fn (array $element) => ! empty($element['tags']['name']))
            ->unique(fn (array $element) => "{$element['type']}/{$element['id']}")
            ->map(function (array $element) {
                $tags = $element['tags'];

                $address = collect([
                    $tags['addr:street'] ?? null,
                    $tags['addr:housenumber'] ?? null,
                ])->filter()->implode(' ');

                // Nodes carry lat/lon directly; way/relation results only have
                // it via the "center" field added by "out center" above.
                $latitude  = $element['lat'] ?? $element['center']['lat'];
                $longitude = $element['lon'] ?? $element['center']['lon'];

                // Node ids are kept bare (unprefixed) to match external_id values
                // already stored for the ~1,400 previously-discovered restaurants,
                // which were all nodes - only way/relation results (new as of this
                // change) get a type prefix, since their numeric ids aren't
                // guaranteed unique against node ids in the same result set.
                $externalId = $element['type'] === 'node'
                    ? (string) $element['id']
                    : "{$element['type']}/{$element['id']}";

                return new PlaceResult(
                    externalId: $externalId,
                    name: $tags['name'],
                    latitude: (float) $latitude,
                    longitude: (float) $longitude,
                    address: $address !== '' ? $address : null,
                    category: $tags['amenity'] ?? $tags['shop'] ?? null,
                );
            })
            ->values()
            ->all();
    }
}
