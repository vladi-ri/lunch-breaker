<?php

namespace App\Console\Commands;

use App\Jobs\FetchMenusJob;
use App\Models\Restaurant;
use Illuminate\Console\Command;

class FetchMenus extends Command
{
    protected $signature = 'menus:fetch';

    protected $description = 'Fetch today\'s menu for every active, non-manual restaurant';

    public function handle(): void
    {
        $restaurants = Restaurant::where('is_active', true)
            ->where('menu_source_type', 'scraper')
            ->get();

        foreach ($restaurants as $restaurant) {
            FetchMenusJob::dispatch($restaurant);
        }

        $this->info("Dispatched menu fetch for {$restaurants->count()} restaurant(s).");
    }
}
