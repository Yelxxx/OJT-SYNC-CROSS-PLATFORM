<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Daily Time Records') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Search --}}
            <div class="bg-white rounded-xl border border-primary-100 shadow-sm p-4">
                <form method="GET" action="{{ route('coordinator.dtr.index') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="q" :value="__('Search student')" />
                        <x-text-input id="q" name="q" type="text" value="{{ $q }}" placeholder="e.g. Peter Parker" class="mt-1 block w-full" />
                    </div>
                    <button type="submit" class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                        Search
                    </button>
                    @if ($q)
                        <a href="{{ route('coordinator.dtr.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Clear</a>
                    @endif
                </form>
            </div>

            {{-- Entries --}}
            <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 bg-cream-100">
                                <th class="px-4 py-3">Student</th>
                                <th class="px-4 py-3">Date</th>
                                <th class="px-4 py-3">Time In</th>
                                <th class="px-4 py-3">Time Out</th>
                                <th class="px-4 py-3">Hours</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Task Summary</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($entries as $entry)
                                <tr class="hover:bg-primary-50 transition">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ $entry->student->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $entry->student->student_no }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $entry->date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $entry->time_in->format('g:i A') }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $entry->time_out?->format('g:i A') ?? '—' }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ number_format($entry->hours, 2) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($entry->verification_status === \App\Models\DtrEntry::VERIFICATION_VERIFIED)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200">Verified</span>
                                        @elseif ($entry->verification_status === \App\Models\DtrEntry::VERIFICATION_DISPUTED)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 ring-1 ring-inset ring-red-200" title="{{ $entry->dispute_reason }}">Disputed</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gold-100 text-gold-800 ring-1 ring-inset ring-gold-300">Pending</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 max-w-xs">
                                        <div class="truncate">{{ $entry->task_summary ?? '—' }}</div>
                                        @if ($entry->dispute_reason)
                                            <div class="text-[11px] text-red-600 mt-0.5 font-medium">Dispute: {{ $entry->dispute_reason }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('coordinator.dtr.print', $entry->student) }}" target="_blank"
                                           class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-800">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                            Timesheet
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-gray-500">No time records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-6">
                {{ $entries->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
