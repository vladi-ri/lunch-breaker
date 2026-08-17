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

/**
 * Controller for managing restaurants in the admin panel.
 * 
 * @extends Controller
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class RestaurantController extends Controller
{
    /**
     * List of valid restaurant categories.
     * 
     * @var    array
     * @access protected
     */
    protected const CATEGORIES = [
        'restaurant',
        'cafe',
        'fast_food',
        'bakery',
        'butcher',
        'supermarket',
        'canteen',
        'food_court',
        'other'
    ];

    /**
     * Display a listing of the restaurants.
     * 
     * @access public
     * @return View
     */
    public function index() : View {
        return view(
            'admin.restaurants.index', [
                'restaurants' => Restaurant::with('office.user')->orderBy('name')->get()
            ]
        );
    }

    /**
     * Show the form for creating a new restaurant.
     *
     * @access public
     * @return View
     */
    public function create() : View {
        return view(
            'admin.restaurants.create', [
                'categories' => self::CATEGORIES,
                'offices'    => Office::with('user')->orderBy('name')->get()
            ]
        );
    }

    /**
     * Store a newly created restaurant in the database.
     * 
     * @param Request           $request  The HTTP request object containing the form data.
     * @param GeocodesAddresses $geocoder Object that can geocode addresses into lat/lng coordinates.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function store(Request $request, GeocodesAddresses $geocoder) : RedirectResponse {
        $validated  = $this->validateRestaurant($request);
        $geocoded   = $geocoder->geocode($validated['address']);

        if ($geocoded === null) {
            return back()
                ->withInput()
                ->withErrors(['address' => 'Could not find that address. Please check it and try again.']);
        }

        $restaurant = Restaurant::create([
            'office_id'        => $validated['office_id'],
            'name'             => $validated['name'],
            'source'           => 'manual',
            'external_id'      => null,
            'address'          => $validated['address'],
            'latitude'         => $geocoded->latitude,
            'longitude'        => $geocoded->longitude,
            'category'         => $validated['category'],
            'is_active'        => true,
            'menu_source_type' => 'manual'
        ]);

        RefreshWalkingDistanceJob::dispatch($restaurant);

        return redirect()
            ->route('admin.restaurants.index')
            ->with('status', 'restaurant-saved');
    }

    /**
     * Show the form for editing the specified restaurant.
     * 
     * @param Restaurant $restaurant The restaurant to be edited.
     * 
     * @access public
     * @return View
     */
    public function edit(Restaurant $restaurant) : View {
        return view('admin.restaurants.edit', [
            'restaurant' => $restaurant,
            'categories' => self::CATEGORIES,
            'offices'    => Office::with('user')->orderBy('name')->get()
        ]);
    }

    /**
     * Update the specified restaurant in the database.
     * 
     * @param Request           $request    The HTTP request object containing the form data.
     * @param Restaurant        $restaurant The restaurant to be updated.
     * @param GeocodesAddresses $geocoder   Object that can geocode addresses into lat/lng coordinates.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function update(Request $request, Restaurant $restaurant, GeocodesAddresses $geocoder) : RedirectResponse {
        $validated      = $this->validateRestaurant($request);
        $addressChanged = $restaurant->address !== $validated['address'];
        $officeChanged  = (int) $restaurant->office_id !== (int) $validated['office_id'];

        $restaurant->fill([
            'name'      => $validated['name'],
            'address'   => $validated['address'],
            'category'  => $validated['category'],
            'office_id' => $validated['office_id']
        ]);

        // If the address has changed, we need to geocode the new address to get the updated latitude and longitude.
        if ($addressChanged) {
            $geocoded = $geocoder->geocode($validated['address']);

            if ($geocoded === null) {
                return back()
                    ->withInput()
                    ->withErrors(['address' => 'Could not find that address. Please check it and try again.']);
            }

            $restaurant->latitude  = $geocoded->latitude;
            $restaurant->longitude = $geocoded->longitude;
        }

        $restaurant->save();

        // Walking distance is calculated from the restaurant's office, so either the
        // address or the office changing means the previously cached distance is stale.
        if ($addressChanged || $officeChanged) {
            RefreshWalkingDistanceJob::dispatch($restaurant);
        }

        return redirect()
            ->route('admin.restaurants.index')
            ->with('status', 'restaurant-saved');
    }

    /**
     * Remove the specified restaurant from the database.
     * 
     * @param Restaurant $restaurant The restaurant to be deleted.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function destroy(Restaurant $restaurant) : RedirectResponse {
        $restaurant->delete();

        return redirect()
            ->route('admin.restaurants.index')
            ->with('status', 'restaurant-deleted');
    }

    /**
     * Validate the restaurant data from the request.
     * 
     * @param Request $request The HTTP request object containing the form data.
     * 
     * @access protected
     * @return array The validated restaurant data.
     */
    protected function validateRestaurant(Request $request) : array {
        return $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'address'   => ['required', 'string', 'max:255'],
            'category'  => ['required', 'in:'.implode(',', self::CATEGORIES)],
            'office_id' => ['required', 'exists:offices,id']
        ]);
    }
}
