<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">Share — {{ $job->title }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <div class="text-center">
                    <div class="inline-block bg-white border border-primary-100 rounded-2xl p-6 shadow-sm">
                        <div style="width: 260px; height: 260px;" class="mx-auto">
                            {!! $qrSvg !!}
                        </div>
                    </div>

                    <p class="mt-6 text-sm text-gray-600">
                        Scan (or print this on a poster) to open the public
                        <span class="font-semibold">{{ $company->name }}</span> opportunities page.
                    </p>
                    <p class="mt-2 text-xs text-gray-400 break-all">
                        <a href="{{ $targetUrl }}" target="_blank" class="hover:text-primary-600 hover:underline">{{ $targetUrl }}</a>
                    </p>

                    <div class="mt-6 flex items-center justify-center gap-3">
                        <button type="button" onclick="window.print()"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                            <x-icon name="printer" class="h-4 w-4" />
                            Print / Save as PDF
                        </button>
                        <a href="{{ route('hr.jobs.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Back to jobs</a>
                    </div>

                    <p class="mt-4 text-xs text-gray-400">
                        The QR code works as long as your company is active on the platform — no expiry, perfect for posters and orientation slides.
                    </p>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
