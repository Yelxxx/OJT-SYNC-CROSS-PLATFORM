<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Daily Time Record - {{ $student->user->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; padding: 0 !important; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-4 md:p-8 font-sans antialiased text-gray-900">
    <div class="max-w-4xl mx-auto mb-4 no-print flex items-center justify-between">
        <a href="javascript:history.back()" class="inline-flex items-center text-sm font-semibold text-gray-600 hover:text-gray-900">
            &larr; Back to Dashboard
        </a>
        <button onclick="window.print()" class="px-5 py-2.5 bg-primary-600 text-white rounded-lg shadow-sm font-medium hover:bg-primary-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print / Save as PDF
        </button>
    </div>

    <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 rounded-xl shadow border border-gray-200">
        {{-- Header --}}
        <div class="text-center pb-6 border-b border-gray-200">
            <h1 class="text-2xl font-bold tracking-tight uppercase text-gray-900">Certificate of Daily Time Record</h1>
            <p class="text-xs uppercase tracking-widest text-gray-500 mt-1">On-The-Job Training / Internship Program</p>
        </div>

        {{-- Meta info --}}
        <div class="grid grid-cols-2 gap-4 py-6 text-sm border-b border-gray-200">
            <div>
                <p class="text-gray-500 text-xs uppercase font-medium">Trainee Name</p>
                <p class="font-bold text-base text-gray-900">{{ $student->user->name }}</p>
                <p class="text-xs text-gray-500 mt-1">Student No: <span class="font-medium text-gray-700">{{ $student->student_no ?? 'N/A' }}</span></p>
            </div>
            <div>
                <p class="text-gray-500 text-xs uppercase font-medium">Partner Host Company</p>
                <p class="font-bold text-base text-gray-900">{{ $company->name ?? 'Unassigned' }}</p>
                <p class="text-xs text-gray-500 mt-1">Program / Major: <span class="font-medium text-gray-700">{{ $student->program ?? 'N/A' }}</span></p>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-3 gap-4 my-6 text-center">
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                <div class="text-xs font-semibold text-gray-500 uppercase">Required Hours</div>
                <div class="text-2xl font-bold text-gray-800 mt-1">{{ number_format($requiredHours, 1) }}h</div>
            </div>
            <div class="p-4 bg-emerald-50 rounded-lg border border-emerald-200">
                <div class="text-xs font-semibold text-emerald-800 uppercase">Verified Hours</div>
                <div class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($verifiedHours, 1) }}h</div>
            </div>
            <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                <div class="text-xs font-semibold text-blue-800 uppercase">Completion Rate</div>
                <div class="text-2xl font-bold text-blue-700 mt-1">
                    {{ $requiredHours > 0 ? round(($verifiedHours / $requiredHours) * 100, 1) : 0 }}%
                </div>
            </div>
        </div>

        {{-- Timesheet Table --}}
        <div class="overflow-x-auto mt-4">
            <table class="min-w-full text-xs text-left border border-gray-300 divide-y divide-gray-300">
                <thead class="bg-gray-100 font-semibold text-gray-700">
                    <tr>
                        <th class="p-2.5 border-r border-gray-300">Date</th>
                        <th class="p-2.5 border-r border-gray-300">Time In</th>
                        <th class="p-2.5 border-r border-gray-300">Time Out</th>
                        <th class="p-2.5 border-r border-gray-300 text-right">Hours</th>
                        <th class="p-2.5 border-r border-gray-300">Status</th>
                        <th class="p-2.5">Tasks / Activities Done</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-gray-800">
                    @forelse ($entries as $entry)
                        <tr>
                            <td class="p-2.5 border-r border-gray-200 font-medium whitespace-nowrap">{{ $entry->date->format('M d, Y') }}</td>
                            <td class="p-2.5 border-r border-gray-200 whitespace-nowrap">{{ $entry->time_in ? $entry->time_in->format('g:i A') : '—' }}</td>
                            <td class="p-2.5 border-r border-gray-200 whitespace-nowrap">{{ $entry->time_out ? $entry->time_out->format('g:i A') : '—' }}</td>
                            <td class="p-2.5 border-r border-gray-200 text-right font-semibold">{{ number_format((float) $entry->hours, 2) }}</td>
                            <td class="p-2.5 border-r border-gray-200 uppercase font-bold text-[10px]">
                                @if($entry->verification_status === 'verified')
                                    <span class="text-emerald-700">Verified</span>
                                @elseif($entry->verification_status === 'disputed')
                                    <span class="text-red-600">Disputed</span>
                                @else
                                    <span class="text-amber-600">Pending</span>
                                @endif
                            </td>
                            <td class="p-2.5 text-gray-600 max-w-xs">{{ $entry->task_summary ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-500">No time records found for this student.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-100 font-bold border-t border-gray-300">
                    <tr>
                        <td colspan="3" class="p-2.5 border-r border-gray-300 text-right uppercase">Total Verified Rendered Hours:</td>
                        <td class="p-2.5 border-r border-gray-300 text-right text-emerald-800 font-extrabold text-sm">{{ number_format($verifiedHours, 2) }}</td>
                        <td colspan="2" class="p-2.5"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Signatures --}}
        <div class="grid grid-cols-2 gap-16 mt-16 pt-8 border-t border-gray-300 text-center text-xs">
            <div>
                <div class="border-b border-gray-400 pb-1 mb-2">
                    <span class="font-bold text-gray-900 uppercase">{{ $student->user->name }}</span>
                </div>
                <p class="text-gray-500 uppercase tracking-wide">Student Trainee Signature</p>
            </div>
            <div>
                <div class="border-b border-gray-400 pb-1 mb-2">
                    <span class="font-bold text-gray-900 uppercase">{{ $company->contact_person ?? 'Industry Supervisor' }}</span>
                </div>
                <p class="text-gray-500 uppercase tracking-wide">Immediate Supervisor / HR Representative</p>
            </div>
        </div>

        <div class="mt-8 text-center text-[10px] text-gray-400 no-print">
            Generated via OJT Sync Ecosystem on {{ now()->format('F d, Y h:i A') }}
        </div>
    </div>
</body>
</html>

