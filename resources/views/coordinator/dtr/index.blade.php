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
                                <th class="px-4 py-3">Method</th>
                                <th class="px-4 py-3">Task Summary</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($entries as $entry)
                                <tr class="hover:bg-primary-50 transition">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900">{{ $entry->student->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $entry->student->student_no }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $entry->date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $entry->time_in->format('g:i A') }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $entry->time_out?->format('g:i A') ?? '—' }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ number_format($entry->hours, 2) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cream-100 text-primary-800 ring-1 ring-inset ring-primary-200 uppercase">
                                            {{ $entry->checkin_method }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500 max-w-xs truncate">{{ $entry->task_summary ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-12 text-center text-gray-500">No time records found.</td>
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
