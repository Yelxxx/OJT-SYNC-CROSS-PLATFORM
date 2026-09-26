<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Evaluation') }} — {{ $evaluation->student->user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @unless ($evaluation->isComplete())
                <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-primary-900 mb-2">Share this link</h3>
                        <p class="text-sm text-gray-600 mb-3">Send it to the student's supervisor to complete the evaluation.</p>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly value="{{ route('evaluations.show', $evaluation->token) }}"
                                class="flex-1 border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" id="eval-link" />
                            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('eval-link').value); this.textContent='Copied!'"
                                class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            @endunless

            <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-primary-900">Results</h3>
                            <p class="text-sm text-gray-500">
                                {{ $evaluation->student->user->name }} · {{ $evaluation->jobPosting?->title ?? '—' }}
                            </p>
                        </div>
                        @if ($evaluation->isComplete())
                            <div class="text-right">
                                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Overall Score</p>
                                <p class="text-3xl font-bold text-primary-700">{{ $evaluation->overall_score }}<span class="text-lg text-gray-400">/5</span></p>
                            </div>
                        @else
                            <x-status-badge status="pending" />
                        @endif
                    </div>

                    @if ($evaluation->isComplete())
                        <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ([
                                'punctuality' => 'Punctuality',
                                'quality_of_work' => 'Quality of Work',
                                'initiative' => 'Initiative',
                                'communication' => 'Communication',
                            ] as $key => $label)
                                <div class="bg-cream-100 border border-primary-100 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-700">{{ $label }}</p>
                                        <p class="text-lg font-bold text-primary-700">{{ $evaluation->$key }}<span class="text-sm text-gray-400">/5</span></p>
                                    </div>
                                    <div class="mt-2 h-2 bg-white rounded-full overflow-hidden border border-primary-100">
                                        <div class="h-2 bg-primary-600 rounded-full" style="width: {{ $evaluation->$key * 20 }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if ($evaluation->comments)
                            <div class="mt-6">
                                <h4 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Comments</h4>
                                <p class="mt-2 text-gray-700 leading-relaxed whitespace-pre-line">{{ $evaluation->comments }}</p>
                            </div>
                        @endif

                        <p class="mt-6 text-xs text-gray-400">Submitted {{ $evaluation->submitted_at?->format('M d, Y g:i A') }}</p>
                    @else
                        <p class="mt-6 text-gray-500">This evaluation has not been submitted yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
