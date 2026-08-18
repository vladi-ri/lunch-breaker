<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-100 leading-tight">
            {{ __('Manage Restaurants') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'restaurant-saved')
                <div class="rounded-lg border border-emerald-700/50 bg-emerald-900/30 px-4 py-3 text-sm text-emerald-200">Restaurant saved.</div>
            @elseif (session('status') === 'restaurant-deleted')
                <div class="rounded-lg border border-emerald-700/50 bg-emerald-900/30 px-4 py-3 text-sm text-emerald-200">Restaurant deleted.</div>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('admin.restaurants.create') }}" class="rounded-lg bg-sky-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-400">
                    Add restaurant
                </a>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 overflow-hidden">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900/60">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">Name</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">Office</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">Category</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">Source</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">Distance</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">Active</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($restaurants as $restaurant)
                            <tr>
                                <td class="px-6 py-3 text-slate-100">{{ $restaurant->name }}</td>
                                <td class="px-6 py-3 text-slate-400">{{ $restaurant->office->name }} ({{ $restaurant->office->user->name }})</td>
                                <td class="px-6 py-3 text-slate-400">{{ $restaurant->category }}</td>
                                <td class="px-6 py-3 text-slate-400">{{ $restaurant->source }}</td>
                                <td class="px-6 py-3 text-slate-400">
                                    @if ($restaurant->walking_duration_seconds)
                                        {{ ceil($restaurant->walking_duration_seconds / 60) }} min
                                    @elseif ($restaurant->walking_distance_meters)
                                        {{ $restaurant->walking_distance_meters }} m
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-slate-300">{{ $restaurant->is_active ? 'Yes' : 'No' }}</td>
                                <td class="px-6 py-3 text-right space-x-3">
                                    <a href="{{ route('admin.restaurants.edit', $restaurant) }}" class="text-sky-400 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.restaurants.destroy', $restaurant) }}" class="inline"
                                        onsubmit="return confirm('Delete this restaurant? This also removes its menus and RSVPs.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-400 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-6 text-center text-slate-500">No restaurants yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
