<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ $job->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Job details --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="shrink-0 h-12 w-12 rounded-lg bg-primary-100 text-primary-700 flex items-center justify-center font-bold">
                                    {{ collect(explode(' ', $job->company->name))->take(2)->map(fn ($w) => strtoupper($w[0]))->join('') }}
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $job->company->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $job->company->address }}</p>
                                </div>
                            </div>
                            <x-status-badge :status="$job->status" />
                        </div>

                        <div class="mt-6 flex flex-wrap gap-4 text-sm text-gray-600">
                            <span class="inline-flex items-center gap-1.5 bg-cream-100 border border-primary-100 rounded-full px-3 py-1">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                {{ $job->slots }} open slot{{ $job->slots !== 1 ? 's' : '' }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 bg-cream-100 border border-primary-100 rounded-full px-3 py-1">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                {{ $job->applications_count }} applicant{{ $job->applications_count !== 1 ? 's' : '' }}
                            </span>
                        </div>

                        <div class="mt-6">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">About the internship</h3>
                            <p class="mt-2 text-gray-700 leading-relaxed whitespace-pre-line">{{ $job->description }}</p>
                        </div>

                        @if ($job->requirements)
                            <div class="mt-6">
                                <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Requirements</h3>
                                <p class="mt-2 text-gray-700 leading-relaxed whitespace-pre-line">{{ $job->requirements }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Apply card --}}
            <div>
                @if ($alreadyApplied)
                    <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                        <div class="p-6 text-center">
                            <div class="mx-auto h-12 w-12 rounded-full bg-primary-100 flex items-center justify-center">
                                <svg class="h-6 w-6 text-primary-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <p class="mt-3 font-semibold text-gray-900">Application submitted</p>
                            <p class="mt-1 text-sm text-gray-500">Track its status in <a href="{{ route('student.applications.index') }}" class="text-primary-600 hover:underline">My Applications</a>.</p>
                        </div>
                    </div>
                @else
                    <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900">Apply for this internship</h3>
                            <p class="mt-1 text-sm text-gray-500">Attach your resume (PDF, DOC, DOCX — max 2MB).</p>

                            <form method="POST" action="{{ route('student.jobs.apply', $job) }}" enctype="multipart/form-data" class="mt-5 space-y-4">
                                @csrf

                                <div>
                                    <x-input-label for="resume" :value="__('Resume (optional)')" />
                                    <input id="resume" type="file" name="resume"
                                        class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 transition" />
                                    <x-input-error :messages="$errors->get('resume')" class="mt-2" />
                                </div>

                                <div>
                                    <x-input-label for="cover_letter" :value="__('Cover Letter (optional)')" />
                                    <textarea id="cover_letter" name="cover_letter" rows="4"
                                        class="mt-1 block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                                        placeholder="Tell the company why you are a great fit...">{{ old('cover_letter') }}</textarea>
                                    <x-input-error :messages="$errors->get('cover_letter')" class="mt-2" />
                                </div>

                                <x-primary-button class="w-full justify-center">
                                    {{ __('Submit Application') }}
                                </x-primary-button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
