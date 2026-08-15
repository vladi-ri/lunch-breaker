<?php

namespace App\Domain\Places\Drivers;

use App\Domain\Places\FindsNearbyPlaces;
use App\Domain\Places\PlaceResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriver implements FindsNearbyPlaces
{
    protected const TYPES = ['restaurant', 'cafe', 'bakery', 'meal_takeaway'];

    public function __construct(
        protected ?string $apiKey,
    ) {}

    public function nearby(float $lat, float $lng, int $radiusMeters): array
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/place/nearbysearch/json', [
            'location' => "{$lat},{$lng}",
            'radius' => $radiusMeters,
            'type' => 'restaurant',
            'key' => $this->apiKey,
        ]);

        if (! $response->ok() || ! in_array($response->json('status'), ['OK', 'ZERO_RESULTS'])) {
            Log::warning('Google Places nearby search failed', ['status' => $response->json('status')]);

            return [];
        }

        return collect($response->json('results', []))
            ->map(fn (array $place) => new PlaceResult(
                externalId: $place['place_id'],
                name: $place['name'],
                latitude: (float) $place['geometry']['location']['lat'],
                longitude: (float) $place['geometry']['location']['lng'],
                address: $place['vicinity'] ?? null,
                category: $place['types'][0] ?? null,
            ))
            ->values()
            ->all();
    }
}
