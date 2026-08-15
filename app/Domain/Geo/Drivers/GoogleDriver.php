<?php

namespace App\Domain\Geo\Drivers;

use App\Domain\Geo\CalculatesWalkingDistance;
use App\Domain\Geo\GeocodesAddresses;
use App\Domain\Geo\GeocodeResult;
use App\Domain\Geo\WalkingResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleDriver implements GeocodesAddresses, CalculatesWalkingDistance
{
    public function __construct(
        protected ?string $apiKey,
    ) {}

    public function geocode(string $address): ?GeocodeResult
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address' => $address,
            'key' => $this->apiKey,
        ]);

        if (! $response->ok() || $response->json('status') !== 'OK') {
            Log::warning('Google geocode failed', ['address' => $address, 'status' => $response->json('status')]);

            return null;
        }

        $result = $response->json('results.0');
        $location = $result['geometry']['location'];

        return new GeocodeResult(
            latitude: (float) $location['lat'],
            longitude: (float) $location['lng'],
            formattedAddress: $result['formatted_address'],
        );
    }

    public function walkingDistance(float $fromLat, float $fromLng, float $toLat, float $toLng): ?WalkingResult
    {
        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => "{$fromLat},{$fromLng}",
            'destinations' => "{$toLat},{$toLng}",
            'mode' => 'walking',
            'key' => $this->apiKey,
        ]);

        if (! $response->ok() || $response->json('status') !== 'OK') {
            Log::warning('Google distance matrix failed', ['status' => $response->json('status')]);

            return null;
        }

        $element = $response->json('rows.0.elements.0');

        if (! $element || $element['status'] !== 'OK') {
            return null;
        }

        return new WalkingResult(
            meters: (int) $element['distance']['value'],
            seconds: (int) $element['duration']['value'],
        );
    }
}
