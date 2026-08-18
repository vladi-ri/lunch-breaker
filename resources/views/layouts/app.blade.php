<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" href="/logo.png">

        <!-- PWA -->
        <link rel="manifest" href="/build/manifest.webmanifest">
        <meta name="theme-color" content="#0f172a">
        <link rel="apple-touch-icon" href="/icons/icon-192.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased bg-slate-950 text-slate-100" x-data="{
        installPromptEvent: null,
        showInstall: false,
        init() {
            window.addEventListener('beforeinstallprompt', (e) => {
                e.preventDefault();
                this.installPromptEvent = e;
                this.showInstall = true;
            });
        },
        async install() {
            if (! this.installPromptEvent) return;
            this.installPromptEvent.prompt();
            await this.installPromptEvent.userChoice;
            this.installPromptEvent = null;
            this.showInstall = false;
        },
    }">
        <div
            x-show="showInstall"
            x-cloak
            class="bg-sky-500 text-white text-sm px-4 py-2 flex items-center justify-between"
        >
            <span>Install Lunch Breaker for quicker access.</span>
            <button @click="install" class="ml-4 bg-white text-sky-600 rounded px-3 py-1 font-semibold">Install</button>
        </div>

        <div class="min-h-screen bg-slate-950">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-slate-800 bg-slate-900/60">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
