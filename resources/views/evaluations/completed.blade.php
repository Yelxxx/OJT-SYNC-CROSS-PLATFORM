<x-guest-layout>
    <div class="text-center">
        <div class="mx-auto h-14 w-14 rounded-full bg-primary-100 flex items-center justify-center">
            <svg class="h-7 w-7 text-primary-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h2 class="mt-4 text-2xl font-bold text-primary-900">Evaluation Submitted</h2>
        <p class="mt-2 text-sm text-gray-600">
            Thank you! The evaluation for <span class="font-medium text-gray-900">{{ $evaluation->student->user->name }}</span>
            has already been completed on {{ $evaluation->submitted_at?->format('M d, Y g:i A') }}.
        </p>
        <p class="mt-4 text-xs text-gray-400">This link can only be used once.</p>
    </div>
</x-guest-layout>
