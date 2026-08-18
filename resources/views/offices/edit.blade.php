<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-100 leading-tight">
            {{ __('Edit Office') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'office-address-changed')
                <div class="rounded-lg border border-emerald-700/50 bg-emerald-900/30 px-4 py-3 text-sm text-emerald-200">
                    &#10003; Office address updated. Discovering restaurants near the new address now &mdash;
                    this can take a minute or two, since it looks up nearby places and walks the distance
                    to each one. Refresh this page shortly to see fresh results.
                </div>
            @elseif (session('status') === 'office-updated')
                <div class="rounded-lg border border-emerald-700/50 bg-emerald-900/30 px-4 py-3 text-sm text-emerald-200">
                    &#10003; Office settings saved.
                </div>
            @elseif (session('status') === 'office-rediscovering')
                <div class="rounded-lg border border-emerald-700/50 bg-emerald-900/30 px-4 py-3 text-sm text-emerald-200">
                    &#10003; Rediscovering nearby restaurants &mdash; refresh the dashboard in a moment to see new results.
                </div>
            @elseif (session('status') === 'office-setup-required')
                <div class="rounded-lg border border-amber-800/60 bg-amber-950/30 px-4 py-3 text-sm text-amber-200">
                    Finish setting up this office before the lunch board can show nearby restaurants.
                </div>
            @endif

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <form method="POST" action="{{ route('offices.update', $office) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('offices._form')
                </form>
            </div>

            @if ($office->latitude !== null)
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                    <h3 class="text-sm font-semibold text-slate-200">Rediscover restaurants</h3>
                    <p class="text-xs text-slate-500 mt-1 mb-4">
                        Re-run restaurant discovery around this office's address without changing it &mdash;
                        useful after widening your radius, or when new place types get added.
                    </p>
                    <form method="POST" action="{{ route('offices.rediscover', $office) }}">
                        @csrf
                        <x-secondary-button type="submit">{{ __('Rediscover now') }}</x-secondary-button>
                    </form>
                </div>
            @endif

            <a href="{{ route('offices.index') }}" class="text-sm text-slate-500 hover:text-slate-300 underline">
                &larr; Back to your offices
            </a>
        </div>
    </div>
</x-app-layout>
