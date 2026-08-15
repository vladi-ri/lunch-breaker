<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Menus') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'menu-saved')
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">Menu saved.</div>
            @elseif (session('status') === 'menu-deleted')
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">Menu deleted.</div>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('admin.menus.create') }}" class="bg-indigo-600 text-white rounded-md px-4 py-2 text-sm font-semibold hover:bg-indigo-700">
                    Add menu
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Restaurant</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Date</th>
                            <th class="px-6 py-3 text-left font-medium text-gray-500">Source</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($menus as $menu)
                            <tr>
                                <td class="px-6 py-3">{{ $menu->restaurant->name }}</td>
                                <td class="px-6 py-3">{{ $menu->date->toFormattedDateString() }}</td>
                                <td class="px-6 py-3 text-gray-500">{{ $menu->source_type }}</td>
                                <td class="px-6 py-3 text-right space-x-3">
                                    <a href="{{ route('admin.menus.edit', $menu) }}" class="text-indigo-600 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" class="inline"
                                        onsubmit="return confirm('Delete this menu?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-6 text-center text-gray-400">No menus yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
