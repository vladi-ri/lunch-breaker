<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Rsvp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controller for managing RSVPs.
 * 
 * @extends Controller
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class RsvpController extends Controller
{
    /**
     * Store a newly created RSVP in the database.
     * 
     * @param Request $request The HTTP request object containing the form data.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse {
        $validated  = $request->validate([
            'restaurant_id' => ['required', 'exists:restaurants,id'],
            'date'          => ['required', 'date']
        ]);

        $restaurant = Restaurant::findOrFail($validated['restaurant_id']);

        abort_unless($restaurant->office_id === $request->user()->office_id, 403);

        Rsvp::updateOrCreate(
            [
                'user_id'       => $request->user()->id,
                'restaurant_id' => $validated['restaurant_id'],
                'date'          => $validated['date']
            ],
            ['status' => 'in']
        );

        return back();
    }

    /**
     * Remove the specified RSVP from the database.
     * 
     * @param Request    $request    The HTTP request object containing the form data.
     * @param Restaurant $restaurant The restaurant for which the RSVP is being removed.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function destroy(Request $request, Restaurant $restaurant) : RedirectResponse {
        $date = $request->validate(['date' => ['required', 'date']])['date'];

        abort_unless($restaurant->office_id === $request->user()->office_id, 403);

        Rsvp::where('user_id', $request->user()->id)
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('date', $date)
            ->delete();

        return back();
    }
}
