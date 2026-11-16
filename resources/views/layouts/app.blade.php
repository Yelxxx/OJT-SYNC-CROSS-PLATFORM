<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'OJT Sync') }}</title>

        <!-- Hide Alpine-controlled elements until Alpine boots -->
        <style>[x-cloak] { display: none !important; }</style>

        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#2E7D32">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="OJT Sync">
        <link rel="apple-touch-icon" href="{{ asset('images/logo-192.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <x-loading-screen />

        <div class="min-h-screen bg-cream-50">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white border-b border-primary-100">
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

        @php
            $status = session('status');
            $error = session('error');
            // These values are shown inline by the auth/profile pages, so they
            // must not also appear as a toast.
            $inlineStatuses = ['password-updated', 'profile-updated', 'verification-link-sent'];
        @endphp

        @if ($error || ($status && ! in_array($status, $inlineStatuses, true)))
            <x-toast :type="$error ? 'error' : 'success'">
                {{ $error ?? $status }}
            </x-toast>
        @endif

        {{-- PWA Install Banner for Mobile & Desktop --}}
        <div x-data="pwaInstallPrompt" x-show="canInstall" x-cloak
             class="fixed bottom-4 inset-x-4 sm:inset-x-auto sm:right-6 sm:w-96 z-50 bg-white border border-primary-200 rounded-xl shadow-2xl p-4 flex items-center justify-between gap-3 animate-bounce-short">
            <div class="flex items-center gap-3 min-w-0">
                <img src="{{ asset('images/logo-192.png') }}" alt="OJT Sync" class="w-10 h-10 rounded-lg shrink-0">
                <div class="min-w-0">
                    <p class="text-xs font-bold text-gray-900 truncate">Install OJT Sync App</p>
                    <p class="text-[11px] text-gray-500 truncate">Add to home screen for faster access</p>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <button type="button" @click="canInstall = false" class="text-xs text-gray-400 hover:text-gray-600 px-1 py-1">
                    Dismiss
                </button>
                <button type="button" @click="installApp"
                        class="px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                    Install
                </button>
            </div>
        </div>
    </body>
</html>
