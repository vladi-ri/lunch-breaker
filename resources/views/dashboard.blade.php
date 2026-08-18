<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-100 leading-tight">
            {{ __('Today\'s Lunch Board') }}
        </h2>
        @if ($topPick)
            @php $topPickCount = $topPick->rsvps->where('status', 'in')->count(); @endphp
            <p class="mt-1 text-sm text-slate-400">
                Current pick:
                <span class="text-sky-400 font-medium">{{ $topPick->name }}</span>
                &mdash; {{ $topPickCount }} colleague{{ $topPickCount === 1 ? '' : 's' }} in
            </p>
        @endif
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'office-updated')
                <div class="rounded-lg border border-emerald-700/50 bg-emerald-900/30 px-4 py-3 text-sm text-emerald-200">
                    Office settings saved.
                </div>
            @endif

            @if ($restaurants->isEmpty())
                <div class="rounded-lg border border-slate-800 bg-slate-900 p-6 text-slate-400">
                    No restaurants found within your walking radius yet. Try running restaurant discovery
                    (<code class="bg-slate-800 text-slate-200 px-1 rounded">php artisan restaurants:discover</code>) or widen your
                    <a href="{{ route('offices.edit', $office) }}" class="text-sky-400 hover:underline">office settings</a>.
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($restaurants as $restaurant)
                    @php
                        $menu = $restaurant->menus->first();
                        $imIn = $restaurant->rsvps->firstWhere('user_id', $userId);
                        $inCount = $restaurant->rsvps->where('status', 'in')->count();
                    @endphp

                    <div class="rounded-xl border border-slate-800 bg-slate-900 overflow-hidden flex flex-col">
                        <div class="p-6 flex-1">
                            <h3 class="text-lg font-semibold text-slate-100">{{ $restaurant->name }}</h3>
                            <p class="text-sm text-slate-500">
                                @if ($restaurant->category)
                                    {{ ucfirst(str_replace('_', ' ', $restaurant->category)) }} &middot;
                                @endif
                                @if ($restaurant->walking_duration_seconds)
                                    {{ ceil($restaurant->walking_duration_seconds / 60) }} min walk
                                @elseif ($restaurant->walking_distance_meters)
                                    {{ $restaurant->walking_distance_meters }} m
                                @endif
                            </p>
                            @if ($restaurant->address)
                                <p class="text-sm text-slate-500 mt-1">{{ $restaurant->address }}</p>
                            @endif

                            <div class="mt-4">
                                @if ($menu && $menu->items->isNotEmpty())
                                    <ul class="text-sm text-slate-300 space-y-1">
                                        @foreach ($menu->items as $item)
                                            <li class="flex justify-between gap-2">
                                                <span>{{ $item->name }}</span>
                                                @if ($item->price !== null)
                                                    <span class="text-slate-500">{{ number_format($item->price, 2) }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @elseif ($menu && $menu->raw_text)
                                    <div x-data="{ expanded: false }" class="text-sm text-slate-400">
                                        <p class="text-xs text-slate-500 italic mb-1">Menu couldn't be split into items automatically &mdash; raw text below.</p>
                                        <p :class="{ 'line-clamp-4': ! expanded }" class="whitespace-pre-line">{{ $menu->raw_text }}</p>
                                        <button type="button" @click="expanded = ! expanded" class="text-sky-400 text-xs font-semibold mt-1">
                                            <span x-text="expanded ? 'Show less' : 'Show more'"></span>
                                        </button>
                                    </div>
                                @else
                                    <p class="text-sm text-slate-500 italic">No menu for today yet.</p>
                                @endif
                            </div>

                            <div class="mt-4 text-sm text-slate-500">
                                @if ($inCount > 0)
                                    {{ $inCount }} colleague{{ $inCount === 1 ? '' : 's' }} in:
                                    {{ $restaurant->rsvps->where('status', 'in')->pluck('user.name')->join(', ') }}
                                @else
                                    Nobody has joined yet.
                                @endif
                            </div>
                        </div>

                        <div class="p-4 border-t border-slate-800">
                            @if ($imIn)
                                <form method="POST" action="{{ route('rsvps.destroy', $restaurant) }}">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="date" value="{{ now()->toDateString() }}">
                                    <button type="submit" class="w-full rounded-lg border border-slate-700 px-4 py-2 text-sm font-medium text-slate-300 transition hover:border-slate-500 hover:text-white">
                                        I'm out
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('rsvps.store') }}">
                                    @csrf
                                    <input type="hidden" name="restaurant_id" value="{{ $restaurant->id }}">
                                    <input type="hidden" name="date" value="{{ now()->toDateString() }}">
                                    <button type="submit" class="w-full rounded-lg bg-sky-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-400">
                                        I'm in
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
