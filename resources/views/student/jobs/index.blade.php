<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Browse Internship Opportunities') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Search & filter --}}
            <div class="bg-white rounded-xl border border-primary-100 shadow-sm p-4 mb-6">
                <form method="GET" action="{{ route('student.jobs.index') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[220px]">
                        <x-input-label for="q" :value="__('Search')" />
                        <x-text-input id="q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="Search title or description..." class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="company" :value="__('Company')" />
                        <select id="company" name="company" class="mt-1 block border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                            <option value="">All companies</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected((string) $filters['company'] === (string) $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                        Search
                    </button>
                    @if ($filters['q'] || $filters['company'])
                        <a href="{{ route('student.jobs.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Clear</a>
                    @endif
                </form>
            </div>

            {{-- Course Match Banner --}}
            @if ($studentProgram)
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center justify-center h-6 w-6 rounded-full bg-emerald-600 text-white font-bold">
                            ✓
                        </span>
                        <div>
                            <span class="font-bold text-emerald-900">Personalized Course Match Active:</span>
                            <span class="text-emerald-700 ml-1">
                                Showing internships matching <strong>{{ \App\Constants\AcademicProgram::label($studentProgram) }}</strong> (or open to all programs).
                            </span>
                        </div>
                    </div>
                    <span class="hidden sm:inline-block px-2.5 py-1 bg-white border border-emerald-200 text-emerald-800 rounded font-semibold">
                        {{ $studentProgram }}
                    </span>
                </div>
            @endif

            @if ($jobs->isEmpty())
                <x-card>
                    <x-empty-state icon="search" title="No internships found" message="No open internship positions currently match your academic program or search criteria." />
                </x-card>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($jobs as $job)
                        <a href="{{ route('student.jobs.show', $job) }}" class="group bg-white rounded-xl border border-primary-100 shadow-sm overflow-hidden hover:border-primary-300 hover:bg-primary-50/40 hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                            <div class="p-6">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="shrink-0 h-11 w-11 rounded-lg bg-primary-100 text-primary-700 flex items-center justify-center font-bold text-sm">
                                            {{ collect(explode(' ', $job->company->name))->take(2)->map(fn ($w) => strtoupper($w[0]))->join('') }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium text-primary-600 truncate">{{ $job->company->name }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $job->company->address }}</p>
                                        </div>
                                    </div>
                                    <x-status-badge :status="$job->status" />
                                </div>

                                <h3 class="mt-4 text-lg font-semibold text-primary-900 group-hover:text-primary-600 transition">{{ $job->title }}</h3>
                                <p class="mt-2 text-sm text-gray-600 line-clamp-3">{{ $job->description }}</p>

                                {{-- Eligible Course Badges --}}
                                <div class="mt-3 flex flex-wrap gap-1">
                                    @if (empty($job->eligible_programs) || in_array('ALL', $job->eligible_programs))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                            🌐 Open to All Courses
                                        </span>
                                    @else
                                        @foreach (array_slice($job->eligible_programs, 0, 3) as $prog)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $prog === $studentProgram ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-gray-100 text-gray-700' }}">
                                                {{ $prog }}
                                            </span>
                                        @endforeach
                                        @if (count($job->eligible_programs) > 3)
                                            <span class="text-[10px] text-gray-400 self-center">+{{ count($job->eligible_programs) - 3 }} more</span>
                                        @endif
                                    @endif
                                </div>

                                <div class="mt-4 flex items-center gap-4 text-xs text-gray-500">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        {{ $job->slots }} slot{{ $job->slots !== 1 ? 's' : '' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        {{ $job->applications_count }} applied
                                    </span>
                                </div>
                            </div>
                            <div class="px-6 py-3 bg-cream-100 border-t border-primary-100 text-sm font-medium text-primary-600 group-hover:bg-primary-50 transition">
                                View details →
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $jobs->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
