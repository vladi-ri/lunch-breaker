<?php

namespace App\Jobs;

use App\Domain\Geo\CalculatesWalkingDistance;
use App\Models\Restaurant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * RefreshWalkingDistanceJob is responsible for calculating and updating the walking distance and duration
 * from the office to a specific restaurant.
 * 
 * @implements ShouldQueue
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class RefreshWalkingDistanceJob implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * Create a new job instance.
     * 
     * @param Restaurant $restaurant The restaurant for which to refresh the walking distance.
     * 
     * @access public
     * @return void
     */
    public function __construct(
        protected Restaurant $restaurant
    ) {}

    /**
     * Execute the job.
     * 
     * @param CalculatesWalkingDistance $walking Object that can calculate walking distance and duration.
     * 
     * @access public
     * @return void
     */
    public function handle(CalculatesWalkingDistance $walking) : void {
        $office = $this->restaurant->office;

        if ($office === null || $office->latitude === null || $office->longitude === null) {
            return;
        }

        $result = $walking->walkingDistance(
            fromLat: (float) $office->latitude,
            fromLng: (float) $office->longitude,
            toLat: (float) $this->restaurant->latitude,
            toLng: (float) $this->restaurant->longitude
        );

        if ($result === null) {
            Log::warning('Could not calculate walking distance', ['restaurant_id' => $this->restaurant->id]);

            return;
        }

        $this->restaurant->update([
            'walking_distance_meters'  => $result->meters,
            'walking_duration_seconds' => $result->seconds,
            'distance_calculated_at'   => now()
        ]);
    }
}
