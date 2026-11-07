<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Intern Absence & Leave Requests') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{
        rejectModalOpen: false,
        rejectActionUrl: '',
        rejectionReason: '',
        openRejectModal(url) {
            this.rejectActionUrl = url;
            this.rejectionReason = '';
            this.rejectModalOpen = true;
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Filter & Summary Bar --}}
            <div class="bg-white rounded-xl border border-primary-100 shadow-sm p-4 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <a href="{{ route('hr.leaves.index') }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ empty($currentStatus) ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }} transition">
                        All ({{ $counts->sum() }})
                    </a>
                    <a href="{{ route('hr.leaves.index', ['status' => 'pending']) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $currentStatus === 'pending' ? 'bg-primary-600 text-white' : 'bg-gold-50 text-gold-700 hover:bg-gold-100' }} transition">
                        Pending ({{ $counts['pending'] ?? 0 }})
                    </a>
                    <a href="{{ route('hr.leaves.index', ['status' => 'approved']) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $currentStatus === 'approved' ? 'bg-primary-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }} transition">
                        Approved ({{ $counts['approved'] ?? 0 }})
                    </a>
                    <a href="{{ route('hr.leaves.index', ['status' => 'rejected']) }}"
                       class="px-3 py-1.5 rounded-lg text-xs font-semibold {{ $currentStatus === 'rejected' ? 'bg-primary-600 text-white' : 'bg-red-50 text-red-700 hover:bg-red-100' }} transition">
                        Rejected ({{ $counts['rejected'] ?? 0 }})
                    </a>
                </div>
            </div>

            {{-- Leaves Table --}}
            <x-card flush>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-cream-100 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Intern</th>
                                <th class="px-6 py-3">Leave Type</th>
                                <th class="px-6 py-3">Dates</th>
                                <th class="px-6 py-3">Reason</th>
                                <th class="px-6 py-3">Proof File</th>
                                <th class="px-6 py-3">Status</th>
                                <th class="px-6 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse ($leaves as $leave)
                                <tr class="hover:bg-primary-50 transition">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-gray-900">{{ $leave->student->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $leave->student->student_no ?? 'ID N/A' }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ \App\Models\InternLeave::TYPES[$leave->leave_type] ?? $leave->leave_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 whitespace-nowrap text-xs">
                                        {{ $leave->start_date->format('M d, Y') }}
                                        @if (!$leave->start_date->equalTo($leave->end_date))
                                            — {{ $leave->end_date->format('M d, Y') }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-gray-700 max-w-xs">
                                        <p class="truncate" title="{{ $leave->reason }}">{{ $leave->reason }}</p>
                                        @if ($leave->rejection_reason)
                                            <p class="text-xs text-red-600 mt-0.5 font-medium">Remarks: {{ $leave->rejection_reason }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        @if ($leave->document_path)
                                            <a href="{{ route('hr.leaves.download', $leave) }}" class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-800 font-semibold hover:underline">
                                                📎 {{ \Illuminate\Support\Str::limit($leave->document_name, 15) }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($leave->isApproved())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                Approved
                                            </span>
                                        @elseif ($leave->isRejected())
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                                Rejected
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gold-100 text-gold-800">
                                                Pending Review
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap text-xs">
                                        @if ($leave->isPending())
                                            <div class="flex items-center justify-end gap-2">
                                                <form method="POST" action="{{ route('hr.leaves.approve', $leave) }}">
                                                    @csrf
                                                    <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded shadow-sm transition">
                                                        Approve
                                                    </button>
                                                </form>
                                                <button type="button" @click="openRejectModal('{{ route('hr.leaves.reject', $leave) }}')"
                                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white font-semibold rounded shadow-sm transition">
                                                    Reject
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">Processed</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm">
                                        No leave requests found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-100">
                    {{ $leaves->links() }}
                </div>
            </x-card>
        </div>

        {{-- Reject Reason Modal --}}
        <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="rejectModalOpen" @click="rejectModalOpen = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div x-show="rejectModalOpen" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form :action="rejectActionUrl" method="POST">
                        @csrf
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2">Reject Leave Request</h3>
                            <p class="text-xs text-gray-500 mb-3">Please specify why this leave request cannot be excused so the intern is informed.</p>
                            <textarea name="rejection_reason" rows="3" required placeholder="e.g. Prior notice required; urgent project deadline."
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 text-sm" x-model="rejectionReason"></textarea>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-2">
                            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md text-xs font-semibold transition">
                                Confirm Rejection
                            </button>
                            <button type="button" @click="rejectModalOpen = false" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 hover:bg-gray-50 transition">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

