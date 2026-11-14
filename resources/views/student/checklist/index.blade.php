<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-primary-900 leading-tight">
                {{ __('Pre-Deployment Clearance & Requirements') }}
            </h2>
            <div>
                @if ($clearanceData['is_cleared'])
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                        🟢 Cleared for Deployment
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                        🔒 Clearance Pending
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-center justify-between">
                    <span>{{ session('status') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
                </div>
            @endif

            {{-- Readiness & Clearance Gate Card --}}
            <x-card>
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <span class="text-xs uppercase font-bold tracking-wider text-primary-600">Deployment Gate</span>
                            <h3 class="text-xl font-extrabold text-gray-900">{{ $company->name ?? 'Assigned Host Company' }}</h3>
                            <p class="text-sm text-gray-500 mt-0.5">
                                Complete your mandatory documents below. Your Practicum Coordinator will review and certify your deployment clearance so you can begin clocking your DTR.
                            </p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-3xl font-extrabold text-primary-900">{{ $completedCount }} / {{ $totalCount }}</span>
                            <p class="text-xs text-gray-500 font-medium">Requirements Approved</p>
                        </div>
                    </div>

                    {{-- Status Banner --}}
                    <div class="mt-4 p-4 rounded-xl border {{ $clearanceData['is_cleared'] ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-cream-50 border-amber-200 text-amber-900' }}">
                        <div class="flex items-start gap-3">
                            <span class="text-xl">{{ $clearanceData['is_cleared'] ? '🟢' : '🔒' }}</span>
                            <div>
                                <p class="text-sm font-bold">
                                    {{ $clearanceData['is_cleared'] ? 'Officially Cleared for Field Duty' : 'DTR Clock Locked Until Cleared' }}
                                </p>
                                <p class="text-xs mt-0.5 {{ $clearanceData['is_cleared'] ? 'text-emerald-700' : 'text-amber-800' }}">
                                    @if ($clearanceData['is_cleared'])
                                        Your coordinator has certified your clearance dossier. You are authorized to begin clocking shifts on your Daily Time Record.
                                    @elseif ($clearanceData['all_docs_approved'])
                                        All your documents have been approved! Awaiting official sign-off from your Practicum Coordinator.
                                    @else
                                        Upload all required documents below for review by your Coordinator and Company HR.
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    @php
                        $percent = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
                    @endphp
                    <div class="mt-4">
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-500" style="width: {{ $percent }}%"></div>
                        </div>
                        <p class="text-right text-xs font-semibold text-primary-700 mt-1">{{ $percent }}% Cleared</p>
                    </div>
                </div>
            </x-card>

            {{-- Checklist Items --}}
            <x-card flush>
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h4 class="text-base font-bold text-gray-900">Pre-Deployment Compliance Dossier</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Upload clear PDF or image copies of each signed CvSU clearance document.</p>
                    </div>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($checklistItems as $item)
                        <div class="p-6 hover:bg-gray-50/50 transition">
                            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                                {{-- Item details --}}
                                <div class="space-y-1.5 flex-1">
                                    <div class="flex items-center gap-2.5">
                                        @if ($item->is_complete || $item->status === \App\Models\InternChecklistItem::STATUS_APPROVED)
                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-emerald-100 text-emerald-600 shrink-0 font-bold text-xs">
                                                ✓
                                            </span>
                                            <h5 class="text-sm font-semibold text-gray-900">{{ $item->title }}</h5>
                                        @elseif ($item->status === \App\Models\InternChecklistItem::STATUS_REJECTED)
                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-rose-100 text-rose-600 shrink-0 font-bold text-xs">
                                                ✕
                                            </span>
                                            <h5 class="text-sm font-semibold text-gray-900">{{ $item->title }}</h5>
                                        @else
                                            <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-gold-100 text-gold-700 font-bold text-xs shrink-0">
                                                !
                                            </span>
                                            <h5 class="text-sm font-semibold text-gray-900">{{ $item->title }}</h5>
                                        @endif
                                    </div>

                                    {{-- Status Badges --}}
                                    <div class="flex items-center gap-2 pt-1">
                                        @if ($item->is_complete || $item->status === \App\Models\InternChecklistItem::STATUS_APPROVED)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                                ✓ Approved & Verified ({{ $item->reviewed_at?->format('M d, Y') ?? $item->completed_at?->format('M d, Y') }})
                                            </span>
                                        @elseif ($item->status === \App\Models\InternChecklistItem::STATUS_REJECTED)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-700 bg-rose-50 px-2 py-0.5 rounded-full border border-rose-200">
                                                ✕ Revision Requested
                                            </span>
                                        @elseif ($item->file_path)
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-sky-700 bg-sky-50 px-2 py-0.5 rounded-full border border-sky-200">
                                                ⏳ Under Review (Submitted {{ $item->submitted_at?->format('M d, Y') }})
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-[11px] font-semibold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full">
                                                Pending submission
                                            </span>
                                        @endif
                                    </div>

                                    @if ($item->file_name)
                                        <div class="flex items-center gap-3 pt-2 text-xs text-gray-600">
                                            <span class="inline-flex items-center gap-1 font-medium text-gray-700 bg-gray-100 px-2.5 py-1 rounded border border-gray-200">
                                                📄 {{ $item->file_name }}
                                            </span>
                                            <a href="{{ route('student.checklist.download', $item) }}" class="text-primary-600 hover:text-primary-800 font-semibold hover:underline">
                                                Download file
                                            </a>
                                        </div>
                                    @endif

                                    @if ($item->notes)
                                        <p class="text-xs text-gray-500 italic mt-1">My note: {{ $item->notes }}</p>
                                    @endif

                                    @if ($item->rejection_reason)
                                        <div class="mt-2 p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-800">
                                            <span class="font-bold">Revision Instructions:</span> {{ $item->rejection_reason }}
                                        </div>
                                    @endif
                                </div>

                                {{-- Upload Action form --}}
                                <div class="shrink-0 w-full md:w-72 bg-gray-50 p-3 rounded-lg border border-gray-200">
                                    <form method="POST" action="{{ route('student.checklist.upload', $item) }}" enctype="multipart/form-data" class="space-y-2">
                                        @csrf
                                        <label class="block text-xs font-semibold text-gray-700">
                                            {{ $item->file_path ? 'Replace / Resubmit File' : 'Upload Document' }}
                                        </label>
                                        <input type="file" name="document" required accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                                               class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 cursor-pointer">
                                        <input type="text" name="notes" placeholder="Optional notes for reviewer"
                                               class="w-full text-xs border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">
                                        <button type="submit" class="w-full py-1.5 px-3 bg-primary-600 hover:bg-primary-700 text-white rounded text-xs font-semibold shadow-sm transition">
                                            Submit for Clearance
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-gray-500 text-sm">
                            No clearance items found.
                        </div>
                    @endforelse
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
