<div
    x-data="loadingScreen"
    x-show="visible"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[100] flex items-center justify-center bg-primary-700"
    role="status"
    aria-busy="true"
>
    <div class="flex flex-col items-center gap-6 px-6 text-center">
        <x-application-logo class="h-14 w-auto" />

        <div class="flex items-center gap-2">
            <span class="h-2.5 w-2.5 rounded-full bg-gold-400 animate-bounce" style="animation-delay: 0ms"></span>
            <span class="h-2.5 w-2.5 rounded-full bg-gold-400 animate-bounce" style="animation-delay: 150ms"></span>
            <span class="h-2.5 w-2.5 rounded-full bg-gold-400 animate-bounce" style="animation-delay: 300ms"></span>
        </div>

        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-primary-200">Loading…</p>
    </div>
</div>
