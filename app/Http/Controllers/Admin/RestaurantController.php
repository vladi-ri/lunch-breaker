<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Geo\GeocodesAddresses;
use App\Http\Controllers\Controller;
use App\Jobs\RefreshWalkingDistanceJob;
use App\Models\Office;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    protected const CATEGORIES = ['restaurant', 'cafe', 'fast_food', 'bakery', 'canteen', 'food_court', 'other'];

    public function index(): View
    {
        return view('admin.restaurants.index', [
            'restaurants' => Restaurant::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.restaurants.create', [
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(Request $request, GeocodesAddresses $geocoder): RedirectResponse
    {
        $validated = $this->validateRestaurant($request);

        $office = Office::first();
        $geocoded = $geocoder->geocode($validated['address']);

        if ($geocoded === null) {
            return back()
                ->withInput()
                ->withErrors(['address' => 'Could not find that address. Please check it and try again.']);
        }

        $restaurant = Restaurant::create([
            'office_id' => $office->id,
            'name' => $validated['name'],
            'source' => 'manual',
            'external_id' => null,
            'address' => $validated['address'],
            'latitude' => $geocoded->latitude,
            'longitude' => $geocoded->longitude,
            'category' => $validated['category'],
            'is_active' => true,
            'menu_source_type' => 'manual',
        ]);

        RefreshWalkingDistanceJob::dispatch($restaurant);

        return redirect()->route('admin.restaurants.index')->with('status', 'restaurant-saved');
    }

    public function edit(Restaurant $restaurant): View
    {
        return view('admin.restaurants.edit', [
            'restaurant' => $restaurant,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function update(Request $request, Restaurant $restaurant, GeocodesAddresses $geocoder): RedirectResponse
    {
        $validated = $this->validateRestaurant($request);

        $addressChanged = $restaurant->address !== $validated['address'];

        $restaurant->fill([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'category' => $validated['category'],
        ]);

        if ($addressChanged) {
            $geocoded = $geocoder->geocode($validated['address']);

            if ($geocoded === null) {
                return back()
                    ->withInput()
                    ->withErrors(['address' => 'Could not find that address. Please check it and try again.']);
            }

            $restaurant->latitude = $geocoded->latitude;
            $restaurant->longitude = $geocoded->longitude;
        }

        $restaurant->save();

        if ($addressChanged) {
            RefreshWalkingDistanceJob::dispatch($restaurant);
        }

        return redirect()->route('admin.restaurants.index')->with('status', 'restaurant-saved');
    }

    public function destroy(Restaurant $restaurant): RedirectResponse
    {
        $restaurant->delete();

        return redirect()->route('admin.restaurants.index')->with('status', 'restaurant-deleted');
    }

    protected function validateRestaurant(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:'.implode(',', self::CATEGORIES)],
        ]);
    }
}
