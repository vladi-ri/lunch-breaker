@php
    $initialItems = isset($menu)
        ? $menu->items->map(fn ($item) => ['name' => $item->name, 'description' => $item->description, 'price' => $item->price])->values()
        : collect([['name' => '', 'description' => '', 'price' => '']]);
@endphp

<div x-data="{ items: {{ $initialItems->toJson() }} }">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="restaurant_id" value="Restaurant" />
            <select id="restaurant_id" name="restaurant_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Select a restaurant&hellip;</option>
                @foreach ($restaurants as $restaurant)
                    <option value="{{ $restaurant->id }}" @selected(old('restaurant_id', $menu->restaurant_id ?? null) == $restaurant->id)>
                        {{ $restaurant->name }}
                    </option>
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
                        class="block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                </div>
                <div class="flex-1">
                    <input type="text" :name="`items[${index}][description]`" x-model="item.description" placeholder="Description (optional)"
                        class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                </div>
                <div class="w-28">
                    <input type="number" step="0.01" min="0" :name="`items[${index}][price]`" x-model="item.price" placeholder="Price"
                        class="block w-full border-gray-300 rounded-md shadow-sm text-sm">
                </div>
                <button type="button" @click="items.splice(index, 1)" class="text-red-500 text-sm px-2 py-2">&times;</button>
            </div>
        </template>

        <button type="button" @click="items.push({ name: '', description: '', price: '' })"
            class="mt-3 text-indigo-600 text-sm font-semibold">
            + Add item
        </button>
    </div>

    <div class="flex items-center gap-4 mt-8">
        <x-primary-button>{{ __('Save menu') }}</x-primary-button>
        <a href="{{ route('admin.menus.index') }}" class="text-sm text-gray-500">Cancel</a>
    </div>
</div>
