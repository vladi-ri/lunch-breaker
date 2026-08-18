<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-100 leading-tight">
            {{ __('Manage Menus') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'menu-saved')
                <div class="rounded-lg border border-emerald-700/50 bg-emerald-900/30 px-4 py-3 text-sm text-emerald-200">Menu saved.</div>
            @elseif (session('status') === 'menu-deleted')
                <div class="rounded-lg border border-emerald-700/50 bg-emerald-900/30 px-4 py-3 text-sm text-emerald-200">Menu deleted.</div>
            @endif

            <div class="flex justify-end">
                <a href="{{ route('admin.menus.create') }}" class="rounded-lg bg-sky-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-sky-400">
                    Add menu
                </a>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-900 overflow-hidden">
                <table class="min-w-full divide-y divide-slate-800 text-sm">
                    <thead class="bg-slate-900/60">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">Restaurant</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">Office</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">Date</th>
                            <th class="px-6 py-3 text-left font-medium text-slate-500">Source</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse ($menus as $menu)
                            <tr>
                                <td class="px-6 py-3 text-slate-100">{{ $menu->restaurant->name }}</td>
                                <td class="px-6 py-3 text-slate-400">{{ $menu->restaurant->office->name }}</td>
                                <td class="px-6 py-3 text-slate-300">{{ $menu->date->toFormattedDateString() }}</td>
                                <td class="px-6 py-3 text-slate-400">{{ $menu->source_type }}</td>
                                <td class="px-6 py-3 text-right space-x-3">
                                    <a href="{{ route('admin.menus.edit', $menu) }}" class="text-sky-400 hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.menus.destroy', $menu) }}" class="inline"
                                        onsubmit="return confirm('Delete this menu?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-400 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-6 text-center text-slate-500">No menus yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
