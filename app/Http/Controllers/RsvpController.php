<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Rsvp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RsvpController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'exists:restaurants,id'],
            'date' => ['required', 'date'],
        ]);

        Rsvp::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'restaurant_id' => $validated['restaurant_id'],
                'date' => $validated['date'],
            ],
            ['status' => 'in'],
        );

        return back();
    }

    public function destroy(Request $request, Restaurant $restaurant): RedirectResponse
    {
        $date = $request->validate(['date' => ['required', 'date']])['date'];

        Rsvp::where('user_id', $request->user()->id)
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('date', $date)
            ->delete();

        return back();
    }
}
