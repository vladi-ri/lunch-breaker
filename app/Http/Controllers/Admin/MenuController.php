<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller for managing menus in the admin panel.
 * 
 * @extends Controller
 * @author  Vladislav Riemer <dev@vladislav-riemer.de>
 */
class MenuController extends Controller
{
    /**
     * Display a listing of the menus.
     * 
     * @access public
     * @return View
     */
    public function index() : View {
        return view(
            'admin.menus.index', [
                'restaurants' => Restaurant::with('office.user')->orderBy('name')->get(),
                'menus'       => Menu::with('restaurant.office')->orderByDesc('date')->limit(50)->get()
            ]
        );
    }

    /**
     * Show the form for creating a new menu.
     * 
     * @access public
     * @return View
     */
    public function create() : View {
        return view('admin.menus.create', [
            'restaurants' => Restaurant::with('office.user')->orderBy('name')->get()
        ]);
    }

    /**
     * Store a newly created menu in the database.
     * 
     * @param Request $request The HTTP request object containing the form data.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function store(Request $request) : RedirectResponse {
        $validated = $this->validateMenu($request);

        $menu      = Menu::updateOrCreate(
            ['restaurant_id' => $validated['restaurant_id'], 'date' => $validated['date']],
            ['source_type' => 'manual', 'fetched_at' => now()]
        );

        $this->syncItems($menu, $validated['items']);

        return redirect()->route('admin.menus.index')->with('status', 'menu-saved');
    }

    /**
     * Show the form for editing the specified menu.
     * 
     * @param Menu $menu The menu to be edited.
     * 
     * @access public
     * @return View
     */
    public function edit(Menu $menu) : View {
        $menu->load('items');

        return view(
            'admin.menus.edit', [
                'menu'        => $menu,
                'restaurants' => Restaurant::with('office.user')->orderBy('name')->get()
            ]);
    }

    /**
     * Update the specified menu in the database.
     * 
     * @param Request $request The HTTP request object containing the form data.
     * @param Menu    $menu    The menu to be updated.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function update(Request $request, Menu $menu) : RedirectResponse {
        $validated = $this->validateMenu($request);

        $menu->update([
            'restaurant_id' => $validated['restaurant_id'],
            'date'          => $validated['date'],
            'source_type'   => 'manual'
        ]);

        $this->syncItems($menu, $validated['items']);

        return redirect()
            ->route('admin.menus.index')
            ->with('status', 'menu-saved');
    }

    /**
     * Remove the specified menu from the database.
     * 
     * @param Menu $menu The menu to be deleted.
     * 
     * @access public
     * @return RedirectResponse
     */
    public function destroy(Menu $menu) : RedirectResponse {
        $menu->delete();

        return redirect()
            ->route('admin.menus.index')
            ->with('status', 'menu-deleted');
    }

    /**
     * Validate the menu data from the request.
     * 
     * @param Request $request The HTTP request object containing the form data.
     * 
     * @access protected
     * @return array The validated menu data.
     */
    protected function validateMenu(Request $request) : array {
        return $request->validate([
            'restaurant_id'       => ['required', 'exists:restaurants,id'],
            'date'                => ['required', 'date'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.name'        => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.price'       => ['nullable', 'numeric', 'min:0']
        ]);
    }

    /**
     * Synchronize the menu items with the database.
     * 
     * @param Menu  $menu  The menu to which the items belong.
     * @param array $items The array of items to be synchronized.
     * 
     * @access protected
     * @return void
     */
    protected function syncItems(Menu $menu, array $items) : void {
        $menu->items()->delete();

        foreach ($items as $index => $item) {
            $menu->items()->create([
                'name'        => $item['name'],
                'description' => $item['description'] ?? null,
                'price'       => $item['price'] ?? null,
                'sort_order'  => $index
            ]);
        }
    }
}
