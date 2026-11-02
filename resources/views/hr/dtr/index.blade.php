<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">Time Records Verification</h2>
    </x-slot>

    <div class="py-12" x-data="{
        selectedIds: [],
        disputeModalOpen: false,
        disputeActionUrl: '',
        disputeReason: '',
        openDisputeModal(url) {
            this.disputeActionUrl = url;
            this.disputeReason = '';
            this.disputeModalOpen = true;
        },
        toggleAll(event) {
            if (event.target.checked) {
                const checkboxes = document.querySelectorAll('.entry-checkbox');
                this.selectedIds = Array.from(checkboxes).map(cb => cb.value);
            } else {
                this.selectedIds = [];
            }
        }
    }">
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

            {{-- Bulk Action Bar --}}
            <div x-show="selectedIds.length > 0" x-cloak class="mb-4 p-3 bg-primary-50 border border-primary-200 rounded-lg flex items-center justify-between shadow-sm">
                <span class="text-sm font-medium text-primary-900">
                    <span class="font-bold" x-text="selectedIds.length"></span> record(s) selected
                </span>
                <div class="flex items-center gap-3">
                    <form method="POST" action="{{ route('hr.dtr.bulk-verify') }}">
                        @csrf
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="entry_ids[]" :value="id">
                        </template>
                        <input type="hidden" name="status" value="verified">
                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-md shadow-sm transition">
                            Verify Selected
                        </button>
                    </form>
                    <form method="POST" action="{{ route('hr.dtr.bulk-verify') }}">
                        @csrf
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="entry_ids[]" :value="id">
                        </template>
                        <input type="hidden" name="status" value="unverified">
                        <button type="submit" class="px-3 py-1.5 bg-gray-500 hover:bg-gray-600 text-white text-xs font-semibold rounded-md shadow-sm transition">
                            Reset to Unverified
                        </button>
                    </form>
                </div>
            </div>

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
                                    <th class="px-4 py-3 w-10">
                                        <input type="checkbox" @change="toggleAll($event)" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                    </th>
                                    <th class="px-4 py-3">Intern</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Time in</th>
                                    <th class="px-4 py-3">Time out</th>
                                    <th class="px-4 py-3">Hours</th>
                                    <th class="px-4 py-3">Task / Notes</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($entries as $entry)
                                    <tr class="hover:bg-primary-50 transition">
                                        <td class="px-4 py-4">
                                            <input type="checkbox" value="{{ $entry->id }}" x-model="selectedIds" class="entry-checkbox rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        </td>
                                        <td class="px-4 py-4 font-medium text-gray-900">
                                            {{ $entry->student->user->name }}
                                        </td>
                                        <td class="px-4 py-4 text-gray-600 whitespace-nowrap">{{ $entry->date->format('M d, Y') }}</td>
                                        <td class="px-4 py-4 text-gray-600 whitespace-nowrap">{{ $entry->time_in?->format('g:i A') }}</td>
                                        <td class="px-4 py-4 text-gray-600 whitespace-nowrap">{{ $entry->time_out?->format('g:i A') }}</td>
                                        <td class="px-4 py-4 font-semibold text-gray-900">{{ number_format((float) $entry->hours, 2) }}</td>
                                        <td class="px-4 py-4 text-gray-500 max-w-[200px]">
                                            <div class="truncate" title="{{ $entry->task_summary }}">{{ $entry->task_summary ?? '—' }}</div>
                                            @if($entry->dispute_reason)
                                                <div class="mt-1 text-[11px] text-red-600 bg-red-50 p-1 rounded border border-red-200">
                                                    <strong>Dispute:</strong> {{ $entry->dispute_reason }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            @if ($entry->verification_status === \App\Models\DtrEntry::VERIFICATION_VERIFIED)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200"><x-icon name="check-circle" class="h-3.5 w-3.5" /> Verified</span>
                                            @elseif ($entry->verification_status === \App\Models\DtrEntry::VERIFICATION_DISPUTED)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 ring-1 ring-inset ring-red-200"><x-icon name="x-circle" class="h-3.5 w-3.5" /> Disputed</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gold-100 text-gold-800 ring-1 ring-inset ring-gold-300">Unverified</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2">
                                                @if ($entry->verification_status !== \App\Models\DtrEntry::VERIFICATION_VERIFIED)
                                                    <form method="POST" action="{{ route('hr.dtr.verify', $entry) }}">
                                                        @csrf
                                                        <input type="hidden" name="status" value="verified">
                                                        <button class="text-xs font-semibold text-emerald-700 hover:underline">Verify</button>
                                                    </form>
                                                @endif
                                                @if ($entry->verification_status !== \App\Models\DtrEntry::VERIFICATION_DISPUTED)
                                                    <button type="button" @click="openDisputeModal('{{ route('hr.dtr.verify', $entry) }}')" class="text-xs font-semibold text-red-600 hover:underline">
                                                        Dispute
                                                    </button>
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

        {{-- Dispute Feedback Modal --}}
        <div x-show="disputeModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="disputeModalOpen" @click="disputeModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="disputeModalOpen" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form :action="disputeActionUrl" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="disputed">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10 text-red-600">
                                    <x-icon name="x-circle" class="h-6 w-6" />
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        Dispute Time Record
                                    </h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500 mb-3">
                                            Please provide a clear reason for disputing this entry so the intern and coordinator can review and rectify it.
                                        </p>
                                        <textarea name="dispute_reason" rows="3" required placeholder="e.g. Discrepancy in recorded hours; trainee left at 3 PM without notice."
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" x-model="disputeReason"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Confirm Dispute
                            </button>
                            <button type="button" @click="disputeModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
