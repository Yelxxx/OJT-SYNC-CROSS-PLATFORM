<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Daily Time Record') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Clock card --}}
            <x-card>
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Total hours logged</p>
                            <p class="mt-1 text-3xl font-bold text-primary-900">{{ number_format($totalHours, 2) }} hrs</p>
                            <p class="mt-1 text-sm text-gray-500">
                                @if ($activeEntry)
                                    <span class="inline-flex items-center gap-1.5 text-emerald-700">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Clocked in since {{ $activeEntry->time_in->format('g:i A') }}
                                    </span>
                                @else
                                    You are currently timed out.
                                @endif
                            </p>
                        </div>

                        <div class="shrink-0">
                            @if ($activeEntry)
                                <form method="POST" action="{{ route('student.dtr.checkout') }}" class="space-y-3">
                                    @csrf
                                    <textarea name="task_summary" rows="2" placeholder="What did you accomplish today? (optional)"
                                        class="block w-full md:w-80 border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm"></textarea>
                                    <x-button variant="gold" type="submit" size="lg" class="w-full">
                                        Time Out
                                    </x-button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('student.dtr.checkin') }}">
                                    @csrf
                                    <x-button type="submit" size="lg" class="w-full">
                                        Time In
                                    </x-button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </x-card>

            {{-- Entry history --}}
            <x-card>
                <x-section-header title="Entry History" />

                <div class="mt-4">
                    @if ($entries->isEmpty())
                        <x-empty-state icon="clock" title="No time entries yet" message="Click Time In to start your first shift and begin logging your hours." />
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        <th class="px-4 py-3">Date</th>
                                        <th class="px-4 py-3">Time In</th>
                                        <th class="px-4 py-3">Time Out</th>
                                        <th class="px-4 py-3">Hours</th>
                                        <th class="px-4 py-3">Task Summary</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($entries as $entry)
                                        <tr class="hover:bg-primary-50 transition">
                                            <td class="px-4 py-3 text-gray-700">{{ $entry->date->format('M d, Y') }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $entry->time_in->format('g:i A') }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $entry->time_out?->format('g:i A') ?? '—' }}</td>
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ number_format($entry->hours, 2) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ $entry->task_summary ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
