<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-primary-900 leading-tight">
                {{ __('OJT Competencies & Skills Portfolio') }}
            </h2>
            <a href="{{ route('student.portfolio.print') }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Official Skills Transcript
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Summary Header Card --}}
            <x-card>
                <div class="p-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <span class="text-xs uppercase font-bold tracking-wider text-primary-600">Internship Credential</span>
                            <h3 class="text-2xl font-extrabold text-gray-900">{{ $student->user->name }}</h3>
                            <p class="text-xs text-gray-500 mt-1">
                                {{ $programLabel }} · Student ID: {{ $student->student_no ?? 'N/A' }}
                            </p>
                            <p class="text-sm font-semibold text-gray-700 mt-2">
                                🏢 {{ $portfolio['company']->name ?? 'Assigned Partner Company' }}
                                @if($portfolio['job'])
                                    · <span class="font-normal text-gray-500">{{ $portfolio['job']->title }}</span>
                                @endif
                            </p>
                        </div>

                        {{-- Total Verified Hours Badge --}}
                        <div class="flex items-center gap-6 shrink-0 bg-gray-50 p-4 rounded-xl border border-gray-200">
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Accredited Hours</p>
                                <p class="text-3xl font-extrabold text-primary-900 mt-0.5">
                                    {{ number_format($portfolio['verifiedHours'], 1) }} <span class="text-sm font-semibold text-gray-400">/ {{ $portfolio['requiredHours'] }}h</span>
                                </p>
                                <p class="text-xs font-semibold text-emerald-600 mt-1">{{ $portfolio['completionPercent'] }}% of requirement completed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>

            {{-- Core Competencies Breakdown --}}
            <x-card flush>
                <div class="p-6 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h4 class="text-base font-bold text-gray-900">Industry Competencies & Skills Gained</h4>
                        <p class="text-xs text-gray-500 mt-0.5">Real-world skill domains practiced throughout your on-the-job training shifts.</p>
                    </div>
                    <span class="text-xs font-bold text-primary-700 bg-primary-50 px-2.5 py-1 rounded-full border border-primary-200">
                        {{ count($portfolio['skills']) }} Core Domains
                    </span>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($portfolio['skills'] as $skill)
                        <div class="p-4 rounded-xl border border-gray-200 bg-white hover:border-primary-300 hover:shadow-sm transition">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h5 class="text-sm font-bold text-gray-900">{{ $skill }}</h5>
                                    <p class="text-xs text-gray-400 mt-0.5">Practiced during verified OJT operational shifts</p>
                                </div>
                                <span class="shrink-0 text-xs font-extrabold text-primary-700 bg-primary-50 px-2 py-0.5 rounded border border-primary-100">
                                    {{ number_format($portfolio['hoursPerSkill'], 1) }} hrs
                                </span>
                            </div>

                            @php
                                $skillProgress = $portfolio['requiredHours'] > 0
                                    ? min(100, round(($portfolio['hoursPerSkill'] / ($portfolio['requiredHours'] / max(1, count($portfolio['skills'])))) * 100))
                                    : 0;
                            @endphp
                            <div class="mt-4">
                                <div class="flex items-center justify-between text-[11px] font-semibold text-gray-500 mb-1">
                                    <span>Domain Proficiency Benchmark</span>
                                    <span class="text-primary-700 font-bold">{{ $skillProgress }}%</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2">
                                    <div class="bg-primary-600 h-2 rounded-full transition-all duration-500" style="width: {{ $skillProgress }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- Supervisor Appraisal & Endorsements --}}
            <x-card>
                <div class="p-6">
                    <h4 class="text-base font-bold text-gray-900 mb-1">Supervisor Performance Appraisal</h4>
                    <p class="text-xs text-gray-500 mb-4">Official employer rating validating your workplace ethics and performance.</p>

                    @if ($portfolio['evaluation'] && $portfolio['evaluation']->isComplete())
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center my-4">
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-[11px] font-bold text-gray-500 uppercase">Punctuality</p>
                                <p class="text-xl font-extrabold text-primary-800 mt-1">{{ $portfolio['evaluation']->punctuality }} / 5</p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-[11px] font-bold text-gray-500 uppercase">Quality of Work</p>
                                <p class="text-xl font-extrabold text-primary-800 mt-1">{{ $portfolio['evaluation']->quality_of_work }} / 5</p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-[11px] font-bold text-gray-500 uppercase">Initiative</p>
                                <p class="text-xl font-extrabold text-primary-800 mt-1">{{ $portfolio['evaluation']->initiative }} / 5</p>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <p class="text-[11px] font-bold text-gray-500 uppercase">Communication</p>
                                <p class="text-xl font-extrabold text-primary-800 mt-1">{{ $portfolio['evaluation']->communication }} / 5</p>
                            </div>
                        </div>

                        <div class="p-4 bg-cream-50 rounded-lg border border-primary-100 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-primary-800 uppercase">Consolidated Assessment Score</span>
                                <p class="text-xs text-gray-500 mt-0.5">Submitted by {{ $portfolio['evaluation']->supervisor_name ?? 'Immediate Supervisor' }}</p>
                            </div>
                            <div class="text-right">
                                <span class="text-3xl font-extrabold text-primary-700">{{ $portfolio['evaluation']->overall_score }}</span>
                                <span class="text-sm font-bold text-gray-400"> / 5.00</span>
                            </div>
                        </div>

                        @if ($portfolio['evaluation']->comments)
                            <div class="mt-4 p-4 rounded-lg bg-gray-50 border border-gray-200 text-xs">
                                <strong class="text-gray-700">Supervisor Remarks:</strong>
                                <p class="text-gray-600 mt-1 italic leading-relaxed">"{{ $portfolio['evaluation']->comments }}"</p>
                            </div>
                        @endif
                    @else
                        <div class="p-6 text-center text-gray-400 text-xs bg-gray-50 rounded-lg border border-dashed border-gray-300">
                            Employer evaluation pending. Performance ratings will appear here once submitted by your industry supervisor.
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>

