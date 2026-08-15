<?php

namespace App\Console\Commands;

use App\Jobs\RefreshWalkingDistanceJob;
use App\Models\Restaurant;
use Illuminate\Console\Command;

class RefreshRestaurantDistances extends Command
{
    protected $signature = 'restaurants:refresh-distances';

    protected $description = 'Recalculate walking distance/duration for every active restaurant';

    public function handle(): void
    {
        $restaurants = Restaurant::where('is_active', true)->get();

        foreach ($restaurants as $restaurant) {
            RefreshWalkingDistanceJob::dispatch($restaurant);
        }

        $this->info("Dispatched distance refresh for {$restaurants->count()} restaurant(s).");
    }
}
