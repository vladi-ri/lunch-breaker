<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller for managing restaurants.
 * 
 * @extends Controller
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class RestaurantController extends Controller
{
    /**
     * Display a listing of the restaurants.
     * 
     * @access public
     * @return View|RedirectResponse
     */
    public function index() : View|RedirectResponse {
        $user        = Auth::user();
        $office      = $user->office;

        if ($office === null) {
            return $user->offices()->exists()
                ? redirect()->route('offices.index')->with('status', 'office-inactive')
                : redirect()->route('offices.create')->with('status', 'office-setup-required');
        }

        if ($office->latitude === null) {
            return redirect()->route('offices.edit', $office)->with('status', 'office-setup-required');
        }

        $today       = today()->toDateString();

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

        $userID      = Auth::id();

        return view(
            'dashboard', [
                'office'      => $office,
                'restaurants' => $restaurants,
                'userId'      => $userID
            ]
        );
    }
}
