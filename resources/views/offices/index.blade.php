<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-100 leading-tight">
            {{ __('My Offices') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'office-activated')
                <div class="rounded-lg border border-emerald-700/50 bg-emerald-900/30 px-4 py-3 text-sm text-emerald-200">
                    &#10003; Active office switched.
                </div>
            @elseif (session('status') === 'office-deleted')
                <div class="rounded-lg border border-emerald-700/50 bg-emerald-900/30 px-4 py-3 text-sm text-emerald-200">
                    &#10003; Office deleted.
                </div>
            @elseif (session('status') === 'office-inactive')
                <div class="rounded-lg border border-amber-800/60 bg-amber-950/30 px-4 py-3 text-sm text-amber-200">
                    You don't have an active office right now &mdash; pick one below.
                </div>
            @endif

            @forelse ($offices as $office)
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-100">
                                {{ $office->name }}
                                @if ($office->id === $activeOfficeID)
                                    <span class="ml-2 inline-flex items-center rounded-full bg-emerald-900/50 border border-emerald-700/50 px-2 py-0.5 text-xs font-medium text-emerald-300">Active</span>
                                @endif
                            </h3>
                            <p class="text-sm text-slate-500 mt-1">{{ $office->address }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 mt-4">
                        @if ($office->id !== $activeOfficeID)
                            <form method="POST" action="{{ route('offices.activate', $office) }}">
                                @csrf
                                <x-secondary-button type="submit">{{ __('Activate') }}</x-secondary-button>
                            </form>
                        @endif

                        <a href="{{ route('offices.edit', $office) }}" class="text-sm text-sky-400 hover:underline">Edit</a>

                        <form method="POST" action="{{ route('offices.destroy', $office) }}"
                            onsubmit="return confirm('Delete {{ $office->name }}? This also removes its restaurants, menus, and RSVPs.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-rose-400 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-slate-800 bg-slate-900 p-6 text-sm text-slate-500">
                    You don't have any offices yet.
                </div>
            @endforelse

            <a href="{{ route('offices.create') }}" class="text-sm text-sky-400 hover:underline">
                + Add another office
            </a>
        </div>
    </div>
</x-app-layout>
