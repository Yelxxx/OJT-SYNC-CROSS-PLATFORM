<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('coordinator.clearance.index') }}" class="text-gray-400 hover:text-gray-600 transition">
                    &larr; Back to Clearance List
                </a>
                <h2 class="font-semibold text-xl text-primary-900 leading-tight">
                    Clearance Dossier: {{ $student->user->name }}
                </h2>
            </div>
            <div>
                @if ($student->is_cleared_for_deployment)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                        🟢 Officially Cleared for Deployment
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        🔒 Deployment Clearance Pending
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-center justify-between">
                    <span>{{ session('status') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
                </div>
            @endif

            <!-- Summary & Clearance Action Card -->
            <div class="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Student & Assignment</span>
                        <h3 class="text-lg font-bold text-gray-900 mt-1">{{ $student->user->name }}</h3>
                        <p class="text-xs text-gray-500">{{ $student->student_no }} &bull; {{ $student->program }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $student->user->email }}</p>
                    </div>

                    <div class="border-t md:border-t-0 md:border-s border-gray-100 md:ps-6">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Host Company & MOA</span>
                        @if ($company)
                            <h4 class="text-sm font-bold text-primary-900 mt-1">{{ $company->name }}</h4>
                            <p class="text-xs text-gray-500">{{ $company->address }}</p>
                            <div class="mt-2">
                                @if ($clearanceData['company_has_moa'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        ✓ Institutional MOA Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        ⚠️ {{ \App\Models\Company::MOA_STATUSES[$company->moa_status] ?? 'No Institutional MOA' }}
                                    </span>
                                @endif
                            </div>
                        @else
                            <p class="text-xs text-amber-700 font-medium mt-1">⚠️ No company assigned yet</p>
                        @endif
                    </div>

                    <div class="border-t md:border-t-0 md:border-s border-gray-100 md:ps-6 flex flex-col justify-center">
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Deployment Status</span>
                        @if ($student->is_cleared_for_deployment)
                            <form method="POST" action="{{ route('coordinator.clearance.students.toggle-deployment', $student) }}">
                                @csrf
                                <input type="hidden" name="is_cleared" value="0">
                                <button type="submit"
                                        onclick="return confirm('Are you sure you want to revoke deployment clearance? The student will not be able to clock in on DTR.')"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-red-50 text-red-700 hover:bg-red-100 border border-red-300 rounded-lg text-xs font-semibold transition">
                                    Revoke Deployment Clearance 🔒
                                </button>
                            </form>
                            <p class="text-[11px] text-gray-400 mt-1.5 text-center">
                                Cleared by {{ $student->clearedBy?->name ?? 'Coordinator' }} on {{ $student->cleared_at?->format('M d, Y') }}
                            </p>
                        @else
                            <form method="POST" action="{{ route('coordinator.clearance.students.toggle-deployment', $student) }}">
                                @csrf
                                <input type="hidden" name="is_cleared" value="1">
                                <button type="submit"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                                    Official Clearance & Unlock DTR 🟢
                                </button>
                            </form>
                            <p class="text-[11px] text-gray-500 mt-1.5 text-center">
                                @if ($clearanceData['can_be_cleared'])
                                    ✓ All requirements verified. Ready to certify.
                                @else
                                    ℹ️ Ensure items are reviewed before certifying.
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Documents Review List -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-bold text-gray-900 text-base">Checklist & Compliance Dossier</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Inspect uploaded proofs, approve satisfactory submissions, or request revisions.</p>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($checklistItems as $item)
                        <div class="p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:bg-gray-50/50 transition">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-bold text-gray-900">{{ $item->title }}</h4>
                                    @if ($item->status === \App\Models\InternChecklistItem::STATUS_APPROVED || $item->is_complete)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            ✓ Approved
                                        </span>
                                    @elseif ($item->status === \App\Models\InternChecklistItem::STATUS_REJECTED)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                            ✕ Revision Requested
                                        </span>
                                    @elseif ($item->file_path)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200">
                                            ⏳ Submitted &bull; Needs Review
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-gray-100 text-gray-600">
                                            Missing / Not Uploaded
                                        </span>
                                    @endif
                                </div>

                                @if ($item->file_path)
                                    <div class="mt-2 flex items-center gap-3">
                                        <a href="{{ route('coordinator.clearance.items.download', $item) }}"
                                           class="inline-flex items-center gap-1.5 text-xs text-primary-600 hover:text-primary-800 font-medium hover:underline">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                            <span>Download {{ $item->file_name }}</span>
                                        </a>
                                        <span class="text-[11px] text-gray-400">
                                            Submitted {{ $item->submitted_at?->diffForHumans() }}
                                        </span>
                                    </div>
                                @endif

                                @if ($item->notes)
                                    <p class="text-xs text-gray-600 mt-1 bg-gray-50 rounded p-2 border border-gray-200">
                                        <span class="font-semibold text-gray-700">Student Note:</span> {{ $item->notes }}
                                    </p>
                                @endif

                                @if ($item->rejection_reason)
                                    <p class="text-xs text-rose-700 mt-1 bg-rose-50 rounded p-2 border border-rose-200">
                                        <span class="font-semibold">Coordinator Feedback:</span> {{ $item->rejection_reason }}
                                    </p>
                                @endif
                            </div>

                            <!-- Review Buttons -->
                            <div class="flex items-center gap-2 shrink-0">
                                @if ($item->file_path)
                                    <!-- Approve Form -->
                                    <form method="POST" action="{{ route('coordinator.clearance.items.review', $item) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit"
                                                class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                                            Approve
                                        </button>
                                    </form>

                                    <!-- Request Revision Form -->
                                    <div x-data="{ open: false }" class="relative">
                                        <button type="button" @click="open = !open"
                                                class="px-3 py-1.5 border border-rose-300 text-rose-700 hover:bg-rose-50 rounded-lg text-xs font-medium transition">
                                            Request Revision
                                        </button>

                                        <div x-show="open" @click.outside="open = false"
                                             class="absolute end-0 mt-2 w-80 bg-white border border-gray-200 rounded-xl shadow-lg p-4 z-20"
                                             style="display: none;">
                                            <form method="POST" action="{{ route('coordinator.clearance.items.review', $item) }}">
                                                @csrf
                                                <input type="hidden" name="status" value="rejected">
                                                <label class="block text-xs font-bold text-gray-700 mb-1">Reason for Revision</label>
                                                <textarea name="rejection_reason" rows="3" required
                                                          placeholder="e.g. Missing signature, blurry scan, notarization required..."
                                                          class="w-full text-xs rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500"></textarea>
                                                <div class="mt-2 flex justify-end gap-2">
                                                    <button type="button" @click="open = false" class="px-2.5 py-1 text-xs text-gray-500">Cancel</button>
                                                    <button type="submit" class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-lg">Send</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Awaiting upload</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 text-sm">
                            No clearance items configured.
                        </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

