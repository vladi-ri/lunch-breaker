<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $office = Office::first();

        if ($office === null || $office->latitude === null) {
            return redirect()->route('office.edit')->with('status', 'office-setup-required');
        }

        $today = today()->toDateString();

        $restaurants = $office->restaurants()
            ->where('is_active', true)
            ->where(function ($query) use ($office) {
                if ($office->max_walking_minutes !== null) {
                    $query->where('walking_duration_seconds', '<=', $office->max_walking_minutes * 60);
                } elseif ($office->max_distance_meters !== null) {
                    $query->where('walking_distance_meters', '<=', $office->max_distance_meters);
                }
            })
            ->with([
                'menus' => fn ($query) => $query->whereDate('date', $today)->with('items'),
                'rsvps' => fn ($query) => $query->whereDate('date', $today)->with('user'),
            ])
            ->orderBy('walking_distance_meters')
            ->get();

        $userId = Auth::id();

        return view('dashboard', [
            'office' => $office,
            'restaurants' => $restaurants,
            'userId' => $userId,
        ]);
    }
}
