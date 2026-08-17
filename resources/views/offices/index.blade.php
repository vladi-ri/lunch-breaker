<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Offices') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'office-activated')
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">
                    &#10003; Active office switched.
                </div>
            @elseif (session('status') === 'office-deleted')
                <div class="bg-green-50 border border-green-200 text-green-800 text-sm rounded-lg p-4">
                    &#10003; Office deleted.
                </div>
            @elseif (session('status') === 'office-inactive')
                <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-4">
                    You don't have an active office right now &mdash; pick one below.
                </div>
            @endif

            @forelse ($offices as $office)
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-800">
                                {{ $office->name }}
                                @if ($office->id === $activeOfficeId)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">Active</span>
                                @endif
                            </h3>
                            <p class="text-sm text-gray-500 mt-1">{{ $office->address }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        @if ($office->id !== $activeOfficeId)
                            <form method="POST" action="{{ route('offices.activate', $office) }}">
                                @csrf
                                <x-secondary-button type="submit">{{ __('Activate') }}</x-secondary-button>
                            </form>
                        @endif

                        <a href="{{ route('offices.edit', $office) }}" class="text-sm text-indigo-600 hover:underline">Edit</a>

                        <form method="POST" action="{{ route('offices.destroy', $office) }}"
                            onsubmit="return confirm('Delete {{ $office->name }}? This also removes its restaurants, menus, and RSVPs.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6 text-sm text-gray-500">
                    You don't have any offices yet.
                </div>
            @endforelse

            <a href="{{ route('offices.create') }}" class="text-sm text-indigo-600 hover:underline">
                + Add another office
            </a>
        </div>
    </div>
</x-app-layout>
