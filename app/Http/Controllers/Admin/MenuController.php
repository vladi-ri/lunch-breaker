<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(): View
    {
        return view('admin.menus.index', [
            'restaurants' => Restaurant::orderBy('name')->get(),
            'menus' => Menu::with('restaurant')->orderByDesc('date')->limit(50)->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.menus.create', [
            'restaurants' => Restaurant::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateMenu($request);

        $menu = Menu::updateOrCreate(
            ['restaurant_id' => $validated['restaurant_id'], 'date' => $validated['date']],
            ['source_type' => 'manual', 'fetched_at' => now()],
        );

        $this->syncItems($menu, $validated['items']);

        return redirect()->route('admin.menus.index')->with('status', 'menu-saved');
    }

    public function edit(Menu $menu): View
    {
        $menu->load('items');

        return view('admin.menus.edit', [
            'menu' => $menu,
            'restaurants' => Restaurant::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $validated = $this->validateMenu($request);

        $menu->update([
            'restaurant_id' => $validated['restaurant_id'],
            'date' => $validated['date'],
            'source_type' => 'manual',
        ]);

        $this->syncItems($menu, $validated['items']);

        return redirect()->route('admin.menus.index')->with('status', 'menu-saved');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return redirect()->route('admin.menus.index')->with('status', 'menu-deleted');
    }

    protected function validateMenu(Request $request): array
    {
        return $request->validate([
            'restaurant_id' => ['required', 'exists:restaurants,id'],
            'date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    protected function syncItems(Menu $menu, array $items): void
    {
        $menu->items()->delete();

        foreach ($items as $index => $item) {
            $menu->items()->create([
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'price' => $item['price'] ?? null,
                'sort_order' => $index,
            ]);
        }
    }
}
