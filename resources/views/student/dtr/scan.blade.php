<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">Scan to Time In</h2>
    </x-slot>

    <div class="py-12" x-data="qrScanner({ redirectUrl: '{{ route('student.dtr.index') }}' })">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <x-card>
                <div class="space-y-4">
                    <p class="text-sm text-gray-600">
                        Point your camera at the <span class="font-semibold">check-in QR</span> displayed
                        at your company. It refreshes every minute, so scan the one shown right now.
                    </p>

                    {{-- Camera viewport --}}
                    <div class="relative aspect-square bg-primary-900 rounded-xl overflow-hidden">
                        <video x-ref="video" playsinline muted class="absolute inset-0 h-full w-full object-cover"
                            :class="cameraState === 'running' ? 'opacity-100' : 'opacity-0'"></video>

                        {{-- Scan frame guide --}}
                        <div x-show="cameraState === 'running'" x-cloak
                            class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="w-3/5 h-3/5 border-2 border-white/80 rounded-2xl shadow-[0_0_0_9999px_rgba(0,0,0,0.35)]"></div>
                        </div>

                        {{-- Idle / starting overlay --}}
                        <div x-show="cameraState !== 'running'"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-3 text-center px-6">
                            <template x-if="cameraState === 'idle'">
                                <div class="space-y-4">
                                    <x-icon name="search" class="h-10 w-10 text-primary-200 mx-auto" />
                                    <button type="button" @click="start()"
                                        class="px-5 py-2.5 bg-white rounded-md font-semibold text-primary-800 text-sm uppercase tracking-wider hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-primary-900">
                                        Open camera
                                    </button>
                                </div>
                            </template>
                            <template x-if="cameraState === 'starting'">
                                <p class="text-primary-100 text-sm animate-pulse">Starting camera…</p>
                            </template>
                        </div>
                    </div>

                    {{-- Status line --}}
                    <p class="text-sm text-gray-500 text-center" x-text="statusLabel"></p>

                    {{-- Result banner --}}
                    <div x-show="result" x-cloak>
                        <div x-show="result && result.type === 'success'"
                            class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800 font-medium"
                            x-text="result?.message"></div>
                        <div x-show="result && result.type === 'notice'"
                            class="rounded-lg bg-gold-50 border border-gold-300 px-4 py-3 text-sm text-gold-800 font-medium"
                            x-text="result?.message"></div>
                        <div x-show="result && result.type === 'error'"
                            class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                            <p class="font-medium" x-text="result?.message"></p>
                            <button type="button" @click="result = null; start()"
                                class="mt-2 text-xs font-semibold uppercase tracking-wider text-red-700 hover:underline">
                                Try again
                            </button>
                        </div>
                    </div>

                    {{-- Graceful fallback: manual check-in still works --}}
                    <div class="pt-2 border-t border-gray-100 text-center">
                        <a href="{{ route('student.dtr.index') }}" class="text-sm font-medium text-gray-500 hover:text-primary-700">
                            Camera not working? Time in manually →
                        </a>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
