<?php

namespace App\Http\Controllers;

use App\Domain\Geo\GeocodesAddresses;
use App\Jobs\DiscoverRestaurantsJob;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for managing a user's personal offices.
 * 
 * @extends Controller
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class OfficeController extends Controller
{
    /**
     * List the current user's offices.
     * 
     * @param Request $request The HTTP request object.
     * 
     * @access public
     * @return View
     */
    public function index(Request $request) : View {
        return view(
            'offices.index', [
                'offices'        => $request->user()->offices()->orderBy('name')->get(),
                'activeOfficeID' => $request->user()->office_id
            ]
        );
    }

    /**
     * Show the form for creating a new office.
     * 
     * @access public
     * @return View
     */
    public function create() : View {
        return view('offices.create');
    }

    /**
     * Store a newly created office.
     * 
     * @param Request           $request  The HTTP request object containing the form data.
     * @param GeocodesAddresses $geocoder Object that can geocode addresses into lat/lng coordinates.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function store(Request $request, GeocodesAddresses $geocoder) : RedirectResponse {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'address'             => ['required', 'string', 'max:255'],
            'max_distance_meters' => ['nullable', 'integer', 'min:1'],
            'max_walking_minutes' => ['nullable', 'integer', 'min:1'],
            'distance_unit'       => ['required', 'in:meters,miles']
        ]);

        $geocoded  = $geocoder->geocode($validated['address']);

        if ($geocoded === null) {
            return back()
                ->withInput()
                ->withErrors(['address' => 'Could not find that address. Please check it and try again.']);
        }

        $office    = Office::create(
            [
                ...$validated,
                'user_id'     => $request->user()->id,
                'latitude'    => $geocoded->latitude,
                'longitude'   => $geocoded->longitude,
                'geocoded_at' => now()
            ]
        );

        DiscoverRestaurantsJob::dispatch($office);

        // The user's first office becomes active automatically; later ones
        // don't steal activation from whatever the user already picked.
        if ($request->user()->office_id === null) {
            $request->user()->update(['office_id' => $office->id]);
        }

        return redirect()
            ->route('offices.edit', $office)
            ->with('status', 'office-address-changed');
    }

    /**
     * Show the form for editing an office.
     * 
     * @param Request $request The HTTP request object.
     * @param Office  $office  The office to edit.
     * 
     * @access public
     * @return View
     */
    public function edit(Request $request, Office $office) : View {
        $this->authorizeOwner($request, $office);

        return view('offices.edit', ['office' => $office]);
    }

    /**
     * Update an office.
     * 
     * @param Request           $request  The HTTP request object containing the form data.
     * @param Office            $office   The office to update.
     * @param GeocodesAddresses $geocoder Object that can geocode addresses into lat/lng coordinates.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function update(Request $request, Office $office, GeocodesAddresses $geocoder) : RedirectResponse {
        $this->authorizeOwner($request, $office);

        $validated           = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'address'             => ['required', 'string', 'max:255'],
            'max_distance_meters' => ['nullable', 'integer', 'min:1'],
            'max_walking_minutes' => ['nullable', 'integer', 'min:1'],
            'distance_unit'       => ['required', 'in:meters,miles']
        ]);

        $office->fill($validated);

        // Must be read before save() clears the dirty state, and reflects the
        // submitted address text rather than geocoded coordinates so that
        // reverting to a previous address is detected too (see below).
        $addressChanged      = $office->isDirty('address');
        $geocoded            = $geocoder->geocode($validated['address']);

        if ($geocoded === null) {
            return back()
                ->withInput()
                ->withErrors(['address' => 'Could not find that address. Please check it and try again.']);
        }

        $office->latitude    = $geocoded->latitude;
        $office->longitude   = $geocoded->longitude;
        $office->geocoded_at = now();
        $office->save();

        if ($addressChanged) {
            // The old restaurant set was discovered around the previous location and its
            // cached walking distances no longer mean anything here. Hide it (rather than
            // delete, to keep any manually entered menus/RSVPs) and discover fresh.
            $office->restaurants()->update(['is_active' => false]);

            DiscoverRestaurantsJob::dispatch($office);
        }

        return redirect()
            ->route('offices.edit', $office)
            ->with('status', $addressChanged ? 'office-address-changed' : 'office-updated');
    }

    /**
     * Delete an office.
     * 
     * @param Request $request The HTTP request object.
     * @param Office  $office  The office to delete.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function destroy(Request $request, Office $office) : RedirectResponse {
        $this->authorizeOwner($request, $office);

        if ($request->user()->office_id === $office->id) {
            $replacement = $request->user()->offices()->where('id', '!=', $office->id)->first();

            $request->user()->update(['office_id' => $replacement?->id]);
        }

        $office->delete();

        return redirect()
            ->route('offices.index')
            ->with('status', 'office-deleted');
    }

    /**
     * Make an office the current user's active office.
     * 
     * @param Request $request The HTTP request object.
     * @param Office  $office  The office to activate.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function activate(Request $request, Office $office) : RedirectResponse {
        $this->authorizeOwner($request, $office);

        $request->user()->update(['office_id' => $office->id]);

        return redirect()
            ->route('offices.index')
            ->with('status', 'office-activated');
    }

    /**
     * Re-run restaurant discovery for an office without changing its
     * address (e.g. after widening the radius, or picking up newly-added
     * source categories like butchers/supermarkets).
     * 
     * @param Request $request The HTTP request object.
     * @param Office  $office  The office to rediscover restaurants for.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function rediscover(Request $request, Office $office) : RedirectResponse {
        $this->authorizeOwner($request, $office);

        DiscoverRestaurantsJob::dispatch($office);

        return redirect()
            ->route('offices.edit', $office)
            ->with('status', 'office-rediscovering');
    }

    /**
     * Abort with a 403 unless the current user owns the given office.
     * 
     * @param Request $request The HTTP request object.
     * @param Office  $office  The office being accessed.
     * 
     * @access private
     * @return void
     */
    private function authorizeOwner(Request $request, Office $office) : void {
        abort_unless($request->user()->ownsOffice($office), 403);
    }
}
