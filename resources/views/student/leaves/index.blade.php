<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Absence & Leave Filing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- File a Leave Card --}}
            <x-card>
                <div class="p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">Submit Absence / Leave Request</h3>
                    <p class="text-xs text-gray-500 mb-5">
                        File an excused absence for sickness, academic school requirements, or emergencies. Host company HR will review and approve.
                    </p>

                    <form method="POST" action="{{ route('student.leaves.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Leave Type</label>
                                <select name="leave_type" required
                                        class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <option value="">Select type...</option>
                                    @foreach (\App\Models\InternLeave::TYPES as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Start Date</label>
                                <input type="date" name="start_date" required min="{{ now()->subDays(7)->toDateString() }}"
                                       class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">End Date</label>
                                <input type="date" name="end_date" required min="{{ now()->subDays(7)->toDateString() }}"
                                       class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Reason / Explanation</label>
                            <textarea name="reason" rows="2" required placeholder="Describe the reason for your absence (e.g. Attending Departmental Final Examination, Medical Consultation)"
                                      class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700">Supporting Document (Optional)</label>
                            <p class="text-[11px] text-gray-400 mb-1">Medical Certificate, University Excuse Letter, or Exam Schedule (PDF, JPG, PNG up to 10MB)</p>
                            <input type="file" name="document" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                                   class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer">
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-md text-xs font-semibold uppercase tracking-wider shadow-sm transition">
                                Submit Leave Request
                            </button>
                        </div>
                    </form>
                </div>
            </x-card>

            {{-- Leave History Card --}}
            <x-card flush>
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h4 class="text-base font-bold text-gray-900">Leave History</h4>
                        <p class="text-xs text-gray-500">Track approvals and review statuses of your filed leaves.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3">Type</th>
                                <th class="px-6 py-3">Dates</th>
                                <th class="px-6 py-3">Reason</th>
                                <th class="px-6 py-3">Proof / File</th>
                                <th class="px-6 py-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-sm">
                            @forelse ($leaves as $leave)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-semibold text-gray-900 whitespace-nowrap">
                                        {{ \App\Models\InternLeave::TYPES[$leave->leave_type] ?? $leave->leave_type }}
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
                                            <p class="text-xs text-red-600 mt-1 font-medium">Rejection Reason: {{ $leave->rejection_reason }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs">
                                        @if ($leave->document_path)
                                            <a href="{{ route('student.leaves.download', $leave) }}" class="inline-flex items-center gap-1 text-primary-600 hover:text-primary-800 font-semibold hover:underline">
                                                📎 Download
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
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-400 text-xs">
                                        No leave requests filed yet.
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
    </div>
</x-app-layout>

