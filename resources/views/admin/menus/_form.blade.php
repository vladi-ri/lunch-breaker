@php
    $initialItems = isset($menu)
        ? $menu->items->map(fn ($item) => ['name' => $item->name, 'description' => $item->description, 'price' => $item->price])->values()
        : collect([['name' => '', 'description' => '', 'price' => '']]);
@endphp

<div x-data="{ items: {{ $initialItems->toJson() }} }">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="restaurant_id" value="Restaurant" />
            <select id="restaurant_id" name="restaurant_id" required class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-900 text-slate-100 focus:border-sky-500 focus:ring-sky-500">
                <option value="" class="bg-slate-900 text-slate-100">Select a restaurant&hellip;</option>
                @foreach ($restaurants->groupBy('office_id') as $officeRestaurants)
                    <optgroup label="{{ $officeRestaurants->first()->office->name }} ({{ $officeRestaurants->first()->office->user->name }})" class="bg-slate-900 text-slate-100">
                        @foreach ($officeRestaurants as $restaurant)
                            <option value="{{ $restaurant->id }}" class="bg-slate-900 text-slate-100" @selected(old('restaurant_id', $menu->restaurant_id ?? null) == $restaurant->id)>
                                {{ $restaurant->name }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('restaurant_id')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="date" value="Date" />
            <x-text-input id="date" name="date" type="date" class="mt-1 block w-full"
                value="{{ old('date', isset($menu) ? $menu->date->toDateString() : now()->toDateString()) }}" required />
            <x-input-error :messages="$errors->get('date')" class="mt-2" />
        </div>
    </div>

    <div class="mt-6">
        <x-input-label value="Menu items" />
        <x-input-error :messages="$errors->get('items')" class="mt-2" />

        <template x-for="(item, index) in items" :key="index">
            <div class="flex gap-3 items-start mt-3">
                <div class="flex-1">
                    <input type="text" :name="`items[${index}][name]`" x-model="item.name" placeholder="Item name"
                        class="block w-full rounded-lg border-slate-700 bg-slate-900 text-slate-100 placeholder-slate-500 text-sm focus:border-sky-500 focus:ring-sky-500" required>
                </div>
                <div class="flex-1">
                    <input type="text" :name="`items[${index}][description]`" x-model="item.description" placeholder="Description (optional)"
                        class="block w-full rounded-lg border-slate-700 bg-slate-900 text-slate-100 placeholder-slate-500 text-sm focus:border-sky-500 focus:ring-sky-500">
                </div>
                <div class="w-28">
                    <input type="number" step="0.01" min="0" :name="`items[${index}][price]`" x-model="item.price" placeholder="Price"
                        class="block w-full rounded-lg border-slate-700 bg-slate-900 text-slate-100 placeholder-slate-500 text-sm focus:border-sky-500 focus:ring-sky-500">
                </div>
                <button type="button" @click="items.splice(index, 1)" class="text-rose-400 text-sm px-2 py-2">&times;</button>
            </div>
        </template>

        <button type="button" @click="items.push({ name: '', description: '', price: '' })"
            class="mt-3 text-sky-400 text-sm font-semibold">
            + Add item
        </button>
    </div>

    <div class="flex items-center gap-4 mt-8">
        <x-primary-button>{{ __('Save menu') }}</x-primary-button>
        <a href="{{ route('admin.menus.index') }}" class="text-sm text-slate-500 hover:text-slate-300">Cancel</a>
    </div>
</div>
