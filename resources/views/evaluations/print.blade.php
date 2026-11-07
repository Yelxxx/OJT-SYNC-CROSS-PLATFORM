<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Performance Evaluation - {{ $student->user->name }}</title>
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
            &larr; Back
        </a>
        <button onclick="window.print()" class="px-5 py-2.5 bg-primary-600 text-white rounded-lg shadow-sm font-medium hover:bg-primary-700 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print / Save as PDF
        </button>
    </div>

    <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 rounded-xl shadow border border-gray-200">
        {{-- Header --}}
        <div class="text-center pb-6 border-b border-gray-200">
            <h1 class="text-2xl font-bold tracking-tight uppercase text-gray-900">Internship Performance Evaluation Report</h1>
            <p class="text-xs uppercase tracking-widest text-gray-500 mt-1">Host Company Supervisor Appraisal</p>
        </div>

        {{-- Meta info --}}
        <div class="grid grid-cols-2 gap-4 py-6 text-sm border-b border-gray-200">
            <div>
                <p class="text-gray-500 text-xs uppercase font-medium">Trainee Name</p>
                <p class="font-bold text-base text-gray-900">{{ $student->user->name }}</p>
                <p class="text-xs text-gray-500 mt-1">Student No: <span class="font-medium text-gray-700">{{ $student->student_no ?? 'N/A' }}</span></p>
                <p class="text-xs text-gray-500">Degree / Program: <span class="font-medium text-gray-700">{{ $student->program ?? 'N/A' }}</span></p>
            </div>
            <div>
                <p class="text-gray-500 text-xs uppercase font-medium">Partner Host Company</p>
                <p class="font-bold text-base text-gray-900">{{ $company->name ?? 'Host Company' }}</p>
                <p class="text-xs text-gray-500 mt-1">Role / Designation: <span class="font-medium text-gray-700">{{ $evaluation->jobPosting?->title ?? 'OJT Trainee' }}</span></p>
                <p class="text-xs text-gray-500">Supervisor Name: <span class="font-medium text-gray-700">{{ $evaluation->supervisor_name ?? ($company->contact_person ?? 'Industry Supervisor') }}</span></p>
            </div>
        </div>

        {{-- Overall Score Banner --}}
        <div class="my-6 p-6 bg-cream-50 rounded-xl border border-primary-100 flex items-center justify-between">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-primary-700">Consolidated Rating</span>
                <h3 class="text-lg font-bold text-gray-900 mt-0.5">Final Performance Assessment</h3>
                <p class="text-xs text-gray-500">Submitted on {{ $evaluation->submitted_at?->format('F d, Y \a\t g:i A') }}</p>
            </div>
            <div class="text-right">
                <span class="text-4xl font-extrabold text-primary-700">{{ number_format((float) $evaluation->overall_score, 2) }}</span>
                <span class="text-xl font-bold text-gray-400"> / 5.00</span>
                <p class="text-xs font-semibold text-emerald-700 mt-0.5">
                    @if($evaluation->overall_score >= 4.5)
                        Outstanding Performance
                    @elseif($evaluation->overall_score >= 3.5)
                        Very Satisfactory
                    @elseif($evaluation->overall_score >= 2.5)
                        Satisfactory
                    @else
                        Needs Improvement
                    @endif
                </p>
            </div>
        </div>

        {{-- Detailed Scoring Rubric Breakdown --}}
        <div class="mt-6">
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3">Evaluation Rubric Breakdown</h4>
            <table class="min-w-full text-xs text-left border border-gray-300 divide-y divide-gray-300">
                <thead class="bg-gray-100 font-semibold text-gray-700 uppercase">
                    <tr>
                        <th class="p-3 border-r border-gray-300">Competency & Assessment Criteria</th>
                        <th class="p-3 border-r border-gray-300 w-36 text-center">Score (1 - 5)</th>
                        <th class="p-3 text-center w-36">Rating Bar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-gray-800">
                    @php
                        $rubrics = [
                            ['Punctuality & Attendance', 'Consistently arrives on time, meets scheduled shifts, and adheres to agreed company hours.', $evaluation->punctuality],
                            ['Quality of Work & Competence', 'Demonstrates technical competence, accuracy, thoroughness, and attention to detail in assigned tasks.', $evaluation->quality_of_work],
                            ['Initiative & Self-Drive', 'Proactively seeks tasks, exhibits problem-solving resourcefulness, and requires minimal supervision.', $evaluation->initiative],
                            ['Communication & Teamwork', 'Communicates effectively with team members and supervisors, accepts constructive feedback, and collaborates well.', $evaluation->communication],
                        ];
                    @endphp

                    @foreach($rubrics as [$title, $desc, $score])
                        <tr>
                            <td class="p-3 border-r border-gray-200">
                                <p class="font-bold text-gray-900 text-sm">{{ $title }}</p>
                                <p class="text-gray-500 mt-0.5 text-[11px]">{{ $desc }}</p>
                            </td>
                            <td class="p-3 border-r border-gray-200 text-center font-extrabold text-base text-primary-800">
                                {{ $score }} <span class="text-xs font-normal text-gray-400">/ 5</span>
                            </td>
                            <td class="p-3 text-center align-middle">
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-primary-600 h-2 rounded-full" style="width: {{ ($score / 5) * 100 }}%"></div>
                                </div>
                                <span class="text-[10px] text-gray-500 font-semibold mt-1 block">{{ ($score / 5) * 100 }}%</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Qualitative Remarks / Comments --}}
        @if ($evaluation->comments)
            <div class="mt-6 p-4 rounded-lg bg-gray-50 border border-gray-200">
                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-600">Supervisor Remarks & Feedback</h4>
                <p class="mt-2 text-xs text-gray-700 leading-relaxed whitespace-pre-line">{{ $evaluation->comments }}</p>
            </div>
        @endif

        {{-- Formal Signature Block --}}
        <div class="grid grid-cols-2 gap-16 mt-16 pt-8 border-t border-gray-300 text-center text-xs">
            <div>
                <div class="border-b border-gray-400 pb-1 mb-2">
                    <span class="font-bold text-gray-900 uppercase">
                        {{ $evaluation->supervisor_name ?? ($company->contact_person ?? 'Industry Supervisor') }}
                    </span>
                </div>
                <p class="text-gray-500 uppercase tracking-wide">Industry Immediate Supervisor</p>
                <p class="text-[10px] text-gray-400 mt-0.5">{{ $company->name }}</p>
            </div>
            <div>
                <div class="border-b border-gray-400 pb-1 mb-2">
                    <span class="font-bold text-gray-900 uppercase">
                        {{ $student->coordinator->name ?? 'OJT Coordinator' }}
                    </span>
                </div>
                <p class="text-gray-500 uppercase tracking-wide">University OJT Coordinator</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Academic Internship Department</p>
            </div>
        </div>

        <div class="mt-8 text-center text-[10px] text-gray-400 no-print">
            Official Evaluation Sheet &bull; Authenticated via OJT Sync Token #{{ substr($evaluation->token, 0, 12) }}...
        </div>
    </div>
</body>
</html>

