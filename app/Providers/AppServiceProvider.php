<?php

namespace App\Providers;

use App\Domain\Geo\CalculatesWalkingDistance;
use App\Domain\Geo\GeocodesAddresses;
use App\Domain\Geo\GeoManager;
use App\Domain\Places\FindsNearbyPlaces;
use App\Domain\Places\PlacesManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GeoManager::class);
        $this->app->bind(GeocodesAddresses::class, fn ($app) => $app->make(GeoManager::class)->driver());
        $this->app->bind(CalculatesWalkingDistance::class, fn ($app) => $app->make(GeoManager::class)->driver());

        $this->app->singleton(PlacesManager::class);
        $this->app->bind(FindsNearbyPlaces::class, fn ($app) => $app->make(PlacesManager::class)->driver());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
