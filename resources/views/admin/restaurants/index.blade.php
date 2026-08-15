<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Restaurants') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'restaurant-saved')
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">Restaurant saved.</div>
            @elseif (session('status') === 'restaurant-deleted')
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">Restaurant deleted.</div>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('admin.restaurants.create') }}" class="bg-indigo-600 text-white rounded-md px-4 py-2 text-sm font-semibold hover:bg-indigo-700">
                    Add restaurant
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Name</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Category</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Source</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Distance</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Active</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($restaurants as $restaurant)
                            <tr>
                                <td class="px-6 py-3">{{ $restaurant->name }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $restaurant->category }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $restaurant->source }}</td>
                                <td class="px-6 py-3 text-gray-500">
                                    @if ($restaurant->walking_duration_seconds)
                                        {{ ceil($restaurant->walking_duration_seconds / 60) }} min
                                    @elseif ($restaurant->walking_distance_meters)
                                        {{ $restaurant->walking_distance_meters }} m
                                    @else
                                        &mdash;
                                    @endif
                                </td>
                                <td class="px-6 py-3">{{ $restaurant->is_active ? 'Yes' : 'No' }}</td>
                                <td class="px-6 py-3 text-right space-x-3">
                                    <a href="{{ route('admin.restaurants.edit', $restaurant) }}" class="text-indigo-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.restaurants.destroy', $restaurant) }}" class="inline"
                                        onsubmit="return confirm('Delete this restaurant? This also removes its menus and RSVPs.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-center text-gray-400">No restaurants yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
