<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="pwa-enabled" content="{{ config('pwa.enabled') ? 'true' : 'false' }}">

        <title>{{ config('app.name', 'OJT Sync') }}</title>

        <!-- Hide Alpine-controlled elements until Alpine boots -->
        <style>[x-cloak] { display: none !important; }</style>

        <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">

        <!-- PWA Manifest -->
        <link rel="manifest" href="{{ url('/manifest.webmanifest') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @vite(['resources/js/registerSW.js'])
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

            <!-- Install Prompt -->
            <div x-data="installPrompt()" x-ref="installPrompt" x-show="showInstallPrompt"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6 sm:px-6 lg:px-8"
                 role="dialog" aria-modal="true" aria-labelledby="install-prompt-title">
                <!-- Blurred backdrop: click it to dismiss -->
                <div class="absolute inset-0 bg-gray-900/40 backdrop-blur-sm"
                     @click="showInstallPrompt = false" aria-hidden="true"></div>
                <div x-show="showInstallPrompt"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 sm:scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 sm:scale-100"
                     x-transition:leave-end="opacity-0 sm:scale-95"
                     class="relative w-full max-w-xs">
                    <!-- Install Prompt -->
                    <div class="bg-white shadow-xl ring-1 ring-black ring-opacity-5 overflow-hidden sm:rounded-lg">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 id="install-prompt-title" class="text-lg font-medium text-gray-900">
                                        Install OJT Sync
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">
                                            Add OJT Sync to your home screen for faster access.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button"
                                    @click="installPWA"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent px-4 py-2 bg-primary-600 text-sm font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:ns">
                                Install
                            </button>
                            <button type="button"
                                    @click="dismissInstallPrompt"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:ns">
                                Not Now
                            </button>
                        </div>
                    </div>
                </div>
            </div>
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
    </body>
</html>
