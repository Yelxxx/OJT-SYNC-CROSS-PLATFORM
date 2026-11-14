<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-primary-900 leading-tight">
                    {{ __('Pre-Deployment Clearance & MOA Tracker') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    CHED-compliant verification of student waivers, medical certificates, and host company MOAs.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                    {{ $clearedCount }} Cleared for Duty
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                    {{ $pendingCount }} Pending Clearance
                </span>
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-gray-900 text-base">Supervised Student Advisees</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Review submitted clearance documents and unlock student DTR clocking.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50/75 text-gray-500 text-xs font-semibold uppercase tracking-wider">
                            <tr>
                                <th class="px-6 py-3.5 text-left">Student Info</th>
                                <th class="px-6 py-3.5 text-left">Assigned Company</th>
                                <th class="px-6 py-3.5 text-left">Company MOA Status</th>
                                <th class="px-6 py-3.5 text-center">Docs Approved</th>
                                <th class="px-6 py-3.5 text-center">Clearance Status</th>
                                <th class="px-6 py-3.5 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($students as $student)
                                @php
                                    $data = $student->clearance_data;
                                @endphp
                                <tr class="hover:bg-cream-50/60 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">{{ $student->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ $student->student_no }} &bull; {{ $student->program }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($student->company)
                                            <div class="font-medium text-primary-900">{{ $student->company->name }}</div>
                                            <div class="text-[11px] text-gray-400">{{ $student->company->address }}</div>
                                        @else
                                            <span class="text-xs text-gray-400 italic">No company assigned</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if ($student->company)
                                            @if ($data['company_has_moa'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    ✓ Active MOA
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                    ⚠️ {{ \App\Models\Company::MOA_STATUSES[$data['company_moa_status']] ?? 'No Active MOA' }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-xs text-gray-400">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="inline-flex items-center gap-1.5 font-medium">
                                            <span class="{{ $data['approved_count'] === $data['total_count'] && $data['total_count'] > 0 ? 'text-emerald-700' : 'text-gray-700' }}">
                                                {{ $data['approved_count'] }} / {{ $data['total_count'] }}
                                            </span>
                                            @if ($data['all_docs_approved'])
                                                <span class="text-xs text-emerald-600">✓</span>
                                            @endif
                                        </div>
                                        <div class="w-20 bg-gray-200 rounded-full h-1.5 mx-auto mt-1">
                                            @php
                                                $pct = $data['total_count'] > 0 ? round(($data['approved_count'] / $data['total_count']) * 100) : 0;
                                            @endphp
                                            <div class="bg-primary-600 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if ($student->is_cleared_for_deployment)
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                                🟢 CLEARED (DTR Active)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                🔒 LOCKED (Pending)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <a href="{{ route('coordinator.clearance.show', $student) }}"
                                           class="inline-flex items-center px-3 py-1.5 border border-primary-600 text-xs font-medium rounded-lg text-primary-700 bg-white hover:bg-primary-50 transition shadow-sm">
                                            Review Dossier &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        No student advisees currently assigned under your supervision.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

