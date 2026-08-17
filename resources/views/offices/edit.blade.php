<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Office') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'office-address-changed')
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">
                    &#10003; Office address updated. Discovering restaurants near the new address now &mdash;
                    this can take a minute or two, since it looks up nearby places and walks the distance
                    to each one. Refresh this page shortly to see fresh results.
                </div>
            @elseif (session('status') === 'office-updated')
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">
                    &#10003; Office settings saved.
                </div>
            @elseif (session('status') === 'office-rediscovering')
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">
                    &#10003; Rediscovering nearby restaurants &mdash; refresh the dashboard in a moment to see new results.
                </div>
            @elseif (session('status') === 'office-setup-required')
                <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-4">
                    Finish setting up this office before the lunch board can show nearby restaurants.
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <form method="POST" action="{{ route('offices.update', $office) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('offices._form')
                </form>
            </div>

            @if ($office->latitude !== null)
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <h3 class="text-sm font-semibold text-gray-700">Rediscover restaurants</h3>
                    <p class="text-xs text-gray-500 mt-1 mb-4">
                        Re-run restaurant discovery around this office's address without changing it &mdash;
                        useful after widening your radius, or when new place types get added.
                    </p>
                    <form method="POST" action="{{ route('offices.rediscover', $office) }}">
                        @csrf
                        <x-secondary-button type="submit">{{ __('Rediscover now') }}</x-secondary-button>
                    </form>
                </div>
            @endif

            <a href="{{ route('offices.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline">
                &larr; Back to your offices
            </a>
        </div>
    </div>
</x-app-layout>
