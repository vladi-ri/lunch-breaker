<?php

namespace App\Http\Controllers;

use App\Domain\Geo\GeocodesAddresses;
use App\Jobs\DiscoverRestaurantsJob;
use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficeSettingsController extends Controller
{
    public function show(): View
    {
        return view('settings.office', [
            'office' => Office::first(),
        ]);
    }

    public function update(Request $request, GeocodesAddresses $geocoder): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'max_distance_meters' => ['nullable', 'integer', 'min:1'],
            'max_walking_minutes' => ['nullable', 'integer', 'min:1'],
            'distance_unit' => ['required', 'in:meters,miles'],
        ]);

        $office = Office::first() ?? new Office;
        $office->fill($validated);

        $geocoded = $geocoder->geocode($validated['address']);

        if ($geocoded === null) {
            return back()
                ->withInput()
                ->withErrors(['address' => 'Could not find that address. Please check it and try again.']);
        }

        $moved = $office->latitude === null
            || round((float) $office->latitude, 4) !== round($geocoded->latitude, 4)
            || round((float) $office->longitude, 4) !== round($geocoded->longitude, 4);

        $office->latitude = $geocoded->latitude;
        $office->longitude = $geocoded->longitude;
        $office->geocoded_at = now();
        $office->save();

        if ($moved) {
            // The old restaurant set was discovered around the previous location and its
            // cached walking distances no longer mean anything here. Hide it (rather than
            // delete, to keep any manually entered menus/RSVPs) and discover fresh.
            $office->restaurants()->update(['is_active' => false]);

            DiscoverRestaurantsJob::dispatch($office);
        }

        return redirect()->route('office.edit')->with('status', 'office-updated');
    }
}
