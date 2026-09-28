<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">Worksite Check-in QR — {{ $company->name }}</h2>
    </x-slot>

    <div class="py-12" x-data="checkinQr({ secondsLeft: {{ $secondsLeft }} })">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <div class="text-center space-y-4">
                    <p class="text-sm text-gray-600">
                        Display this screen at the worksite. Interns scan it to clock in —
                        <span class="font-semibold">the code rotates every minute</span>, so keep it live
                        (an always-on screen or tablet works best).
                    </p>

                    {{-- Live QR --}}
                    <div class="inline-block bg-white border border-primary-100 rounded-2xl p-6 shadow-sm">
                        <div x-show="! error" style="width: 300px; height: 300px;" class="mx-auto transition-opacity duration-200">
                            <div x-ref="qr" class="h-full w-full [&>svg]:h-full [&>svg]:w-full">
                                {!! $qrSvg !!}
                            </div>
                        </div>

                        <div x-show="error" x-cloak style="width: 300px; height: 300px;"
                            class="mx-auto flex items-center justify-center text-sm text-red-600 px-4 text-center">
                            Lost connection to the refresh feed — check your internet and reload this page.
                        </div>
                    </div>

                    {{-- Countdown ring --}}
                    <div class="flex items-center justify-center gap-3 text-sm">
                        <span class="relative inline-flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                        </span>
                        <span class="text-gray-500">
                            New code in <span class="font-semibold text-gray-900" x-text="secondsLeft">…</span>s
                        </span>
                    </div>

                    <div class="pt-2 border-t border-gray-100">
                        <a href="{{ route('hr.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700">Back to dashboard</a>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>

<script>
    /**
     * Kiosk loop (CROSS_PLATFORM_PLAN.md, Phase 3): poll the token endpoint a
     * few seconds before each window rolls over and swap in the fresh SVG.
     * Inline (not in app.js) because only this page ever runs it.
     */
    document.addEventListener('alpine:init', () => {
        Alpine.data('checkinQr', (initialState) => ({
            secondsLeft: initialState.secondsLeft ?? 60,
            error: false,
            timer: null,

            init() {
                this.scheduleNextRefresh();
            },

            scheduleNextRefresh() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.refresh(), Math.max(1, this.secondsLeft - 3) * 1000);
            },

            async refresh() {
                try {
                    const response = await fetch('{{ route('hr.checkin.qr.token') }}', {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });

                    if (! response.ok) {
                        throw new Error('refresh failed');
                    }

                    const data = await response.json();

                    this.$refs.qr.innerHTML = data.svg;
                    this.secondsLeft = data.seconds_left;
                    this.error = false;
                } catch (e) {
                    // Transient network hiccup: retry shortly rather than
                    // leaving a stale-but-valid QR on screen.
                    this.secondsLeft = 5;
                    this.error = true;
                }

                this.scheduleNextRefresh();
            },
        }));
    });
</script>
