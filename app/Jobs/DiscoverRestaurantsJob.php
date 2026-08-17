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
     * The office's geocoded_at value at dispatch time, captured as a plain
     * string (not an Eloquent attribute) so SerializesModels doesn't
     * silently refresh it to the office's *current* value when the job is
     * unserialized off the queue — it needs to stay frozen at what it was
     * when this run was kicked off, so a stale run can detect that it was
     * superseded by a later address change.
     * 
     * @var    string|null
     * @access protected
     */
    protected ?string $expectedGeocodedAt;

    /**
     * Create a new job instance.
     *
     * @param Office $office The office for which to discover nearby restaurants.
     *
     * @access public
     * @return void
     */
    public function __construct(
        protected Office $office
    ) {
        $this->expectedGeocodedAt = $office->geocoded_at?->toJSON();
    }

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

        // The Overpass/Google lookup above can take several seconds. If the
        // office's address changed again while it was in flight, this run
        // is for a superseded location — writing its results now would
        // resurrect restaurants that the newer address change already
        // deactivated, leaving both address's restaurants active at once.
        $current    = Office::find($this->office->id);

        if ($current === null || $current->geocoded_at?->toJSON() !== $this->expectedGeocodedAt) {
            Log::info(
                'Discarding stale restaurant discovery run: office address changed again mid-flight', [
                    'office_id' => $this->office->id
                ]
            );

            return;
        }

        $driverName   = config('services.places.driver', 'osm') === 'google' ? 'google_places' : 'osm';
        $foundIDs     = [];

        foreach ($results as $place) {
            $foundIDs[] = $place->externalId;

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

        // Reconcile rather than only add: anything previously active for this
        // office+source that this run didn't find (e.g. it belonged to a
        // since-superseded address) gets deactivated here, so a run is
        // always self-contained and doesn't depend on a caller having
        // pre-cleared the old set.
        Restaurant::where('office_id', $this->office->id)
            ->where('source', $driverName)
            ->where('is_active', true)
            ->whereNotIn('external_id', $foundIDs)
            ->update(['is_active' => false]);
    }
}
