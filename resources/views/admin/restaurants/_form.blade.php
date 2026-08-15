<div>
    <x-input-label for="name" value="Name" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
        value="{{ old('name', $restaurant->name ?? '') }}" required />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="address" value="Street address" />
    <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
        value="{{ old('address', $restaurant->address ?? '') }}" required />
    <x-input-error :messages="$errors->get('address')" class="mt-2" />
    <p class="text-xs text-gray-400 mt-1">Geocoded automatically when saved.</p>
</div>

<div class="mt-4">
    <x-input-label for="category" value="Category" />
    <select id="category" name="category" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
        @foreach ($categories as $category)
            <option value="{{ $category }}" @selected(old('category', $restaurant->category ?? null) === $category)>
                {{ ucfirst(str_replace('_', ' ', $category)) }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('category')" class="mt-2" />
</div>

<div class="flex items-center gap-4 mt-8">
    <x-primary-button>{{ __('Save restaurant') }}</x-primary-button>
    <a href="{{ route('admin.restaurants.index') }}" class="text-sm text-gray-500">Cancel</a>
</div>
