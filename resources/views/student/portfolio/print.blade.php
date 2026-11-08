<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official OJT Competency Transcript - {{ $student->user->name }}</title>
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
            &larr; Back to Portfolio
        </a>
        <button onclick="window.print()" class="px-5 py-2.5 bg-primary-600 text-white rounded-lg shadow-sm font-medium hover:bg-primary-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print / Save as PDF
        </button>
    </div>

    <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 rounded-xl shadow border border-gray-200">
        {{-- Formal Header --}}
        <div class="text-center pb-6 border-b border-gray-300">
            <h1 class="text-2xl font-extrabold tracking-tight uppercase text-gray-900">Certificate of OJT Competency & Experience Transcript</h1>
            <p class="text-xs uppercase tracking-widest text-gray-500 mt-1">Official Practicum Learning Outcomes & Workplace Skill Acquisition</p>
        </div>

        {{-- Trainee and Host Company Info --}}
        <div class="grid grid-cols-2 gap-4 py-6 text-xs border-b border-gray-300">
            <div>
                <p class="text-gray-500 uppercase font-semibold">Trainee Candidate</p>
                <p class="font-bold text-base text-gray-900 mt-0.5">{{ $student->user->name }}</p>
                <p class="text-gray-600 mt-1">Student No: <span class="font-medium text-gray-800">{{ $student->student_no ?? 'N/A' }}</span></p>
                <p class="text-gray-600">Degree / Program: <span class="font-medium text-gray-800">{{ $programLabel }}</span></p>
            </div>
            <div>
                <p class="text-gray-500 uppercase font-semibold">Host Organization / Company</p>
                <p class="font-bold text-base text-gray-900 mt-0.5">{{ $portfolio['company']->name ?? 'Assigned Partner Company' }}</p>
                <p class="text-gray-600 mt-1">Designation: <span class="font-medium text-gray-800">{{ $portfolio['job']->title ?? 'Internship Trainee' }}</span></p>
                <p class="text-gray-600">Total Verified Hours: <span class="font-bold text-primary-700">{{ number_format($portfolio['verifiedHours'], 1) }} / {{ $portfolio['requiredHours'] }} hrs</span></p>
            </div>
        </div>

        {{-- Competency Breakdown Table --}}
        <div class="mt-6">
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-3">Verified Workplace Competencies & Learning Domains</h4>
            <table class="min-w-full text-xs text-left border border-gray-300 divide-y divide-gray-300">
                <thead class="bg-gray-100 font-bold text-gray-700 uppercase">
                    <tr>
                        <th class="p-3 border-r border-gray-300">Domain / Skill Competency</th>
                        <th class="p-3 border-r border-gray-300 w-36 text-center">Accredited Hours</th>
                        <th class="p-3 w-40 text-center">Demonstrated Level</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-gray-800">
                    @foreach ($portfolio['skills'] as $skill)
                        <tr>
                            <td class="p-3 border-r border-gray-200">
                                <p class="font-bold text-gray-900 text-sm">{{ $skill }}</p>
                                <p class="text-gray-500 text-[11px] mt-0.5">Applied in authentic workplace scenarios and supervised operations.</p>
                            </td>
                            <td class="p-3 border-r border-gray-200 text-center font-extrabold text-sm text-primary-800">
                                {{ number_format($portfolio['hoursPerSkill'], 1) }} hrs
                            </td>
                            <td class="p-3 text-center align-middle">
                                <span class="inline-block px-2.5 py-1 rounded bg-emerald-50 text-emerald-800 font-bold text-[11px] border border-emerald-200">
                                    Proficient & Validated
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-100 font-bold border-t border-gray-300">
                    <tr>
                        <td class="p-3 border-r border-gray-300 uppercase text-right">Total Verified Practicum Hours:</td>
                        <td class="p-3 border-r border-gray-300 text-center text-primary-800 font-extrabold text-sm">{{ number_format($portfolio['verifiedHours'], 1) }}</td>
                        <td class="p-3 text-center text-emerald-800 font-bold">{{ $portfolio['completionPercent'] }}% Complete</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Appraisal Summary --}}
        @if ($portfolio['evaluation'] && $portfolio['evaluation']->isComplete())
            <div class="mt-6 p-4 rounded-lg bg-gray-50 border border-gray-200">
                <div class="flex items-center justify-between border-b border-gray-200 pb-2 mb-2">
                    <span class="text-xs font-bold text-gray-700 uppercase">Industry Supervisor Performance Rating</span>
                    <span class="text-xs font-extrabold text-primary-700">Overall Score: {{ $portfolio['evaluation']->overall_score }} / 5.00</span>
                </div>
                <div class="grid grid-cols-4 gap-2 text-center text-[11px] text-gray-600">
                    <div>Punctuality: <strong>{{ $portfolio['evaluation']->punctuality }}/5</strong></div>
                    <div>Quality of Work: <strong>{{ $portfolio['evaluation']->quality_of_work }}/5</strong></div>
                    <div>Initiative: <strong>{{ $portfolio['evaluation']->initiative }}/5</strong></div>
                    <div>Communication: <strong>{{ $portfolio['evaluation']->communication }}/5</strong></div>
                </div>
                @if ($portfolio['evaluation']->comments)
                    <p class="mt-2 text-xs italic text-gray-600">"{{ $portfolio['evaluation']->comments }}"</p>
                @endif
            </div>
        @endif

        {{-- Formal Signatures --}}
        <div class="grid grid-cols-2 gap-16 mt-16 pt-8 border-t border-gray-300 text-center text-xs">
            <div>
                <div class="border-b border-gray-400 pb-1 mb-2">
                    <span class="font-bold text-gray-900 uppercase">
                        {{ $portfolio['evaluation']->supervisor_name ?? ($portfolio['company']->contact_person ?? 'Industry Supervisor') }}
                    </span>
                </div>
                <p class="text-gray-500 uppercase tracking-wide">Industry Mentor / Supervisor</p>
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $portfolio['company']->name ?? 'Host Company' }}</p>
            </div>
            <div>
                <div class="border-b border-gray-400 pb-1 mb-2">
                    <span class="font-bold text-gray-900 uppercase">
                        {{ $student->coordinator->name ?? 'University OJT Coordinator' }}
                    </span>
                </div>
                <p class="text-gray-500 uppercase tracking-wide">University Practicum Coordinator</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Academic Internship Department</p>
            </div>
        </div>

        <div class="mt-8 text-center text-[10px] text-gray-400 no-print">
            Official Skills Transcript &bull; Issued through OJT Sync Academic-to-Industry Platform on {{ now()->format('F d, Y') }}
        </div>
    </div>
</body>
</html>

