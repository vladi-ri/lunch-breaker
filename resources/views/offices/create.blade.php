<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-100 leading-tight">
            {{ __('Add an Office') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'office-setup-required')
                <div class="rounded-lg border border-amber-800/60 bg-amber-950/30 px-4 py-3 text-sm text-amber-200">
                    Set up your first office before the lunch board can show nearby restaurants.
                </div>
            @endif

            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <form method="POST" action="{{ route('offices.store') }}" class="space-y-6">
                    @csrf

                    @include('offices._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
