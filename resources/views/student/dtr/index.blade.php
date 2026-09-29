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
                            <p class="mt-1 text-3xl font-bold text-primary-900">
                                {{ number_format($totalHours, 2) }} hrs
                            </p>
                            <p class="mt-1 text-sm text-gray-500">
                                @if ($activeEntry)
                                    <span class="inline-flex items-center gap-1.5 text-emerald-700">
                                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Clocked in since {{ $activeEntry->time_in->format('g:i A') }}
                                    </span>
                                @else
                                    <span class="text-gray-500">You are currently timed out.</span>
                                @endif
                            </p>

                            {{-- Live analog clock while timed in: the moving second,
                            minute, and hour hands prove the shift is being counted.
                            The page reloaded on time in, so startedAt is server truth. --}}
                            @if ($activeEntry)
                                <div x-data="liveClock({ startedAt: @js($activeEntry->time_in->toIso8601String()) })"
                                     class="mt-4 flex items-center gap-4">
                                    <svg viewBox="0 0 120 120" class="h-28 w-28 shrink-0" aria-hidden="true">
                                        <circle cx="60" cy="60" r="56" class="fill-white stroke-primary-200" stroke-width="3" />

                                        @foreach (range(0, 11) as $tick)
                                            @php
                                                $angle = deg2rad($tick * 30);
                                                $outerX = 60 + 50 * sin($angle);
                                                $outerY = 60 - 50 * cos($angle);
                                                $innerX = 60 + 45 * sin($angle);
                                                $innerY = 60 - 45 * cos($angle);
                                            @endphp
                                            <line x1="{{ $innerX }}" y1="{{ $innerY }}" x2="{{ $outerX }}" y2="{{ $outerY }}"
                                                  class="stroke-primary-200" stroke-width="2" stroke-linecap="round" />
                                        @endforeach

                                        <g :transform="`rotate(${hourDeg} 60 60)`">
                                            <line x1="60" y1="60" x2="60" y2="40" class="stroke-primary-900" stroke-width="4.5" stroke-linecap="round" />
                                        </g>
                                        <g :transform="`rotate(${minuteDeg} 60 60)`">
                                            <line x1="60" y1="60" x2="60" y2="32" class="stroke-primary-600" stroke-width="3" stroke-linecap="round" />
                                        </g>
                                        <g :transform="`rotate(${secondDeg} 60 60)`">
                                            <line x1="60" y1="66" x2="60" y2="27" class="stroke-gold-500" stroke-width="1.5" stroke-linecap="round" />
                                        </g>
                                        <circle cx="60" cy="60" r="2.5" class="fill-primary-900" />
                                    </svg>

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Current shift</p>
                                        <p class="text-2xl font-bold text-primary-900 tabular-nums" x-text="elapsedLabel">0h 00m 00s</p>
                                        <p class="text-xs text-gray-400">Clock hands move in real time — hours bank when you time out.</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="shrink-0">
                            @if ($activeEntry)
                                <form method="POST" action="{{ route('student.dtr.checkout') }}">
                                    @csrf
                                    <textarea name="task_summary" rows="2" placeholder="What did you accomplish today? (optional)"
                                        class="block w-full md:w-80 border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm"></textarea>
                                    <button type="submit"
                                        class="mt-3 w-full inline-flex justify-center items-center px-4 py-2 bg-gold-400 border border-transparent rounded-md font-semibold text-primary-900 uppercase tracking-wider hover:bg-gold-300 focus:outline-none focus:ring-2 focus:ring-gold-400 focus:ring-offset-2 text-sm">
                                        Time Out
                                    </button>
                                </form>
                            @else
                                <div>
                                    <form method="POST" action="{{ route('student.dtr.checkin') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full inline-flex justify-center items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-white uppercase tracking-wider hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 text-sm">
                                            Time In
                                        </button>
                                    </form>
                                </div>
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
