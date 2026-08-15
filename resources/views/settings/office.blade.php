<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Office Settings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'office-setup-required')
                <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-4">
                    Set your office address before the lunch board can show nearby restaurants.
                </div>
            @elseif (session('status') === 'office-rediscovering')
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">
                    Rediscovering nearby restaurants &mdash; refresh the dashboard in a moment to see new results.
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <form method="POST" action="{{ route('office.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="name" value="Office name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                            value="{{ old('name', $office?->name) }}" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="address" value="Street address" />
                        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
                            value="{{ old('address', $office?->address) }}" required />
                        <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        @if ($office?->geocoded_at)
                            <p class="text-xs text-gray-400 mt-1">
                                Last geocoded {{ $office->geocoded_at->diffForHumans() }}
                                ({{ $office->latitude }}, {{ $office->longitude }})
                            </p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="max_walking_minutes" value="Max walking minutes" />
                            <x-text-input id="max_walking_minutes" name="max_walking_minutes" type="number" min="1"
                                class="mt-1 block w-full" value="{{ old('max_walking_minutes', $office?->max_walking_minutes) }}" />
                            <x-input-error :messages="$errors->get('max_walking_minutes')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="max_distance_meters" value="Max distance (meters)" />
                            <x-text-input id="max_distance_meters" name="max_distance_meters" type="number" min="1"
                                class="mt-1 block w-full" value="{{ old('max_distance_meters', $office?->max_distance_meters) }}" />
                            <x-input-error :messages="$errors->get('max_distance_meters')" class="mt-2" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 -mt-4">
                        Set a walking-minutes limit or a distance limit (or both) — restaurants outside these will be excluded from the board.
                    </p>

                    <div>
                        <x-input-label for="distance_unit" value="Display unit" />
                        <select id="distance_unit" name="distance_unit" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="meters" @selected(old('distance_unit', $office?->distance_unit ?? 'meters') === 'meters')>Meters</option>
                            <option value="miles" @selected(old('distance_unit', $office?->distance_unit) === 'miles')>Miles</option>
                        </select>
                        <x-input-error :messages="$errors->get('distance_unit')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Save') }}</x-primary-button>
                    </div>
                </form>
            </div>

            @if ($office?->latitude !== null)
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700">Rediscover restaurants</h3>
                    <p class="text-xs text-gray-500 mt-1 mb-4">
                        Re-run restaurant discovery around your current office address without changing it &mdash;
                        useful after widening your radius, or when new place types get added.
                    </p>
                    <form method="POST" action="{{ route('office.rediscover') }}">
                        @csrf
                        <x-secondary-button>{{ __('Rediscover now') }}</x-secondary-button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
