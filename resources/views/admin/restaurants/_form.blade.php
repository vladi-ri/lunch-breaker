<div>
    <x-input-label for="office_id" value="Office" />
    <select id="office_id" name="office_id" required class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-900 text-slate-100 focus:border-sky-500 focus:ring-sky-500">
        @foreach ($offices as $office)
            <option value="{{ $office->id }}" class="bg-slate-900 text-slate-100" @selected((int) old('office_id', $restaurant->office_id ?? null) === $office->id)>
                {{ $office->name }} ({{ $office->user->name }})
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('office_id')" class="mt-2" />
</div>

<div class="mt-4">
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
    <p class="text-xs text-slate-500 mt-1">Geocoded automatically when saved.</p>
</div>

<div class="mt-4">
    <x-input-label for="category" value="Category" />
    <select id="category" name="category" required class="mt-1 block w-full rounded-lg border-slate-700 bg-slate-900 text-slate-100 focus:border-sky-500 focus:ring-sky-500">
        @foreach ($categories as $category)
            <option value="{{ $category }}" class="bg-slate-900 text-slate-100" @selected(old('category', $restaurant->category ?? null) === $category)>
                {{ ucfirst(str_replace('_', ' ', $category)) }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('category')" class="mt-2" />
</div>

<div class="flex items-center gap-4 mt-8">
    <x-primary-button>{{ __('Save restaurant') }}</x-primary-button>
    <a href="{{ route('admin.restaurants.index') }}" class="text-sm text-slate-500 hover:text-slate-300">Cancel</a>
</div>
