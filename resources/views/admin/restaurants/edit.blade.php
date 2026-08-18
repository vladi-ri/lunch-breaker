<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-100 leading-tight">
            {{ __('Edit Restaurant') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <form method="POST" action="{{ route('admin.restaurants.update', $restaurant) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.restaurants._form', ['categories' => $categories, 'restaurant' => $restaurant])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
