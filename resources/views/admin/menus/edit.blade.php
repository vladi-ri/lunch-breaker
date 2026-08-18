<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-100 leading-tight">
            {{ __('Edit Menu') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="rounded-xl border border-slate-800 bg-slate-900 p-6">
                <form method="POST" action="{{ route('admin.menus.update', $menu) }}">
                    @csrf
                    @method('PUT')
                    @include('admin.menus._form', ['restaurants' => $restaurants, 'menu' => $menu])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
