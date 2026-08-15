<?php

namespace App\Console\Commands;

use App\Jobs\DiscoverRestaurantsJob;
use App\Models\Office;
use Illuminate\Console\Command;

class DiscoverRestaurants extends Command
{
    protected $signature = 'restaurants:discover';

    protected $description = 'Discover nearby restaurants for every office via the configured places driver';

    public function handle(): void
    {
        $offices = Office::whereNotNull('latitude')->whereNotNull('longitude')->get();

        foreach ($offices as $office) {
            DiscoverRestaurantsJob::dispatch($office);
        }

        $this->info("Dispatched restaurant discovery for {$offices->count()} office(s).");
    }
}
