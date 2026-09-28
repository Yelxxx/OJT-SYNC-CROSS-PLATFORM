<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">Time Records Verification</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Summary + filter --}}
            <div class="bg-white rounded-xl border border-primary-100 shadow-sm p-4 mb-6 flex flex-wrap items-end gap-4">
                <div>
                    <x-input-label for="status" :value="__('Filter by status')" />
                    <select id="status" name="status" class="mt-1 block border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                        <option value="">All statuses</option>
                        @foreach (\App\Models\DtrEntry::VERIFICATION_STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" form="filter-form"
                        class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                    Filter
                </button>
                @if ($filters['status'])
                    <a href="{{ route('hr.dtr.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Clear</a>
                @endif

                <div class="ms-auto flex gap-6 text-sm">
                    <span class="text-gray-500"><span class="font-bold text-gray-900">{{ $counts['verified'] ?? 0 }}</span> verified</span>
                    <span class="text-gray-500"><span class="font-bold text-gold-600">{{ $counts['unverified'] ?? 0 }}</span> unverified</span>
                    <span class="text-gray-500"><span class="font-bold text-red-600">{{ $counts['disputed'] ?? 0 }}</span> disputed</span>
                </div>
            </div>
            <form id="filter-form" method="GET" action="{{ route('hr.dtr.index') }}" class="hidden"></form>

            @if ($entries->isEmpty())
                <x-card>
                    <x-empty-state icon="clock" title="No time records" message="Once your interns clock out, their hours will appear here for verification." />
                </x-card>
            @else
                <x-card flush>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 bg-cream-100">
                                    <th class="px-4 py-3">Intern</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Time in</th>
                                    <th class="px-4 py-3">Time out</th>
                                    <th class="px-4 py-3">Hours</th>
                                    <th class="px-4 py-3">Task</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($entries as $entry)
                                    <tr class="hover:bg-primary-50 transition">
                                        <td class="px-4 py-4 font-medium text-gray-900">{{ $entry->student->user->name }}</td>
                                        <td class="px-4 py-4 text-gray-600">{{ $entry->date->format('M d, Y') }}</td>
                                        <td class="px-4 py-4 text-gray-600">{{ $entry->time_in?->format('g:i A') }}</td>
                                        <td class="px-4 py-4 text-gray-600">{{ $entry->time_out?->format('g:i A') }}</td>
                                        <td class="px-4 py-4 font-semibold text-gray-900">{{ number_format((float) $entry->hours, 2) }}</td>
                                        <td class="px-4 py-4 text-gray-500 max-w-[200px] truncate" title="{{ $entry->task_summary }}">{{ $entry->task_summary ?? '—' }}</td>
                                        <td class="px-4 py-4">
                                            @if ($entry->verification_status === \App\Models\DtrEntry::VERIFICATION_VERIFIED)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200"><x-icon name="check-circle" class="h-3.5 w-3.5" /> Verified</span>
                                            @elseif ($entry->verification_status === \App\Models\DtrEntry::VERIFICATION_DISPUTED)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 ring-1 ring-inset ring-red-200"><x-icon name="x-circle" class="h-3.5 w-3.5" /> Disputed</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gold-100 text-gold-800 ring-1 ring-inset ring-gold-300">Unverified</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center gap-2">
                                                @if ($entry->verification_status !== \App\Models\DtrEntry::VERIFICATION_VERIFIED)
                                                    <form method="POST" action="{{ route('hr.dtr.verify', $entry) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="verified">
                                                        <button class="text-xs font-semibold text-emerald-700 hover:underline">Verify</button>
                                                    </form>
                                                @endif
                                                @if ($entry->verification_status !== \App\Models\DtrEntry::VERIFICATION_DISPUTED)
                                                    <form method="POST" action="{{ route('hr.dtr.verify', $entry) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="disputed">
                                                        <button class="text-xs font-semibold text-red-600 hover:underline">Dispute</button>
                                                    </form>
                                                @endif
                                                @if ($entry->verification_status !== \App\Models\DtrEntry::VERIFICATION_UNVERIFIED)
                                                    <form method="POST" action="{{ route('hr.dtr.verify', $entry) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="unverified">
                                                        <button class="text-xs font-semibold text-gray-500 hover:underline">Undo</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <div class="mt-6">{{ $entries->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
