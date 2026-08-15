<?php

namespace App\Domain\Geo\Drivers;

use App\Domain\Geo\CalculatesWalkingDistance;
use App\Domain\Geo\GeocodesAddresses;
use App\Domain\Geo\GeocodeResult;
use App\Domain\Geo\WalkingResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OsmDriver implements GeocodesAddresses, CalculatesWalkingDistance
{
    // The public OSRM demo server's `foot` profile returns an accurate route
    // distance but an unrealistic duration (tuned closer to driving speeds), so
    // duration is derived here from distance at a standard walking pace instead
    // of trusting the API's `duration` field.
    protected const WALKING_METERS_PER_SECOND = 1.4;

    public function __construct(
        protected string $osrmBaseUrl,
        protected string $userAgent = 'LunchBreakerApp/1.0 (+http://lunch-breaker.test)',
    ) {}

    public function geocode(string $address): ?GeocodeResult
    {
        $response = Http::withHeaders(['User-Agent' => $this->userAgent])
            ->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address,
                'format' => 'json',
                'limit' => 1,
            ]);

        if (! $response->ok() || empty($response->json())) {
            Log::warning('OSM geocode failed', ['address' => $address, 'status' => $response->status()]);

            return null;
        }

        $result = $response->json()[0];

        return new GeocodeResult(
            latitude: (float) $result['lat'],
            longitude: (float) $result['lon'],
            formattedAddress: $result['display_name'],
        );
    }

    public function walkingDistance(float $fromLat, float $fromLng, float $toLat, float $toLng): ?WalkingResult
    {
        $url = sprintf(
            '%s/route/v1/foot/%F,%F;%F,%F',
            rtrim($this->osrmBaseUrl, '/'),
            $fromLng,
            $fromLat,
            $toLng,
            $toLat,
        );

        $response = Http::withHeaders(['User-Agent' => $this->userAgent])
            ->get($url, ['overview' => 'false']);

        if (! $response->ok() || ($response->json('code') !== 'Ok')) {
            Log::warning('OSRM walking distance failed', [
                'from' => [$fromLat, $fromLng],
                'to' => [$toLat, $toLng],
                'status' => $response->status(),
            ]);

            return null;
        }

        $route = $response->json('routes.0');

        if (! $route) {
            return null;
        }

        $meters = (int) round($route['distance']);

        return new WalkingResult(
            meters: $meters,
            seconds: (int) round($meters / self::WALKING_METERS_PER_SECOND),
        );
    }
}
