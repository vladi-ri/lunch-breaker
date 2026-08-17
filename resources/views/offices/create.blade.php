<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add an Office') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'office-setup-required')
                <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-lg p-4">
                    Set up your first office before the lunch board can show nearby restaurants.
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                <form method="POST" action="{{ route('offices.store') }}" class="space-y-6">
                    @csrf

                    @include('offices._form')
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
