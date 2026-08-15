<?php

namespace App\Jobs;

use App\Domain\Places\FindsNearbyPlaces;
use App\Models\Office;
use App\Models\Restaurant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * DiscoverRestaurantsJob is responsible for discovering nearby restaurants based on the office location.
 * 
 * @implements ShouldQueue
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class DiscoverRestaurantsJob implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * The default search radius in meters for discovering restaurants.
     * 
     * @var    int
     * @access protected
     */
    protected const DEFAULT_SEARCH_RADIUS_METERS = 1500;

    /**
     * Create a new job instance.
     * 
     * @param Office $office The office for which to discover nearby restaurants.
     * 
     * @access public
     * @return void
     */
    public function __construct(
        protected Office $office,
    ) {}

    /**
     * Execute the job.
     * 
     * @param FindsNearbyPlaces $places Object that can find nearby places based on coordinates.
     * 
     * @access public
     * @return void
     */
    public function handle(FindsNearbyPlaces $places) : void {
        // Check if the office has valid latitude and longitude before proceeding
        if ($this->office->latitude === null || $this->office->longitude === null) {
            Log::warning('Cannot discover restaurants: office is not geocoded', ['office_id' => $this->office->id]);

            return;
        }

        $radius     = $this->office->max_distance_meters ?? self::DEFAULT_SEARCH_RADIUS_METERS;

        $results    = $places->nearby(
            lat:          (float) $this->office->latitude,
            lng:          (float) $this->office->longitude,
            radiusMeters: $radius
        );

        $driverName = config('services.places.driver', 'osm') === 'google' ? 'google_places' : 'osm';

        foreach ($results as $place) {
            $restaurant = Restaurant::updateOrCreate(
                [
                    'office_id'   => $this->office->id,
                    'source'      => $driverName,
                    'external_id' => $place->externalId
                ],
                [
                    'name'      => $place->name,
                    'address'   => $place->address,
                    'latitude'  => $place->latitude,
                    'longitude' => $place->longitude,
                    'category'  => $place->category,
                    'is_active' => true
                ]
            );

            RefreshWalkingDistanceJob::dispatch($restaurant);
        }
    }
}
