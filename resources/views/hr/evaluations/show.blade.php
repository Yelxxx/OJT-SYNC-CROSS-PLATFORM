<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-primary-900 leading-tight">
                {{ __('Evaluation') }} — {{ $evaluation->student->user->name }}
            </h2>
            @if ($evaluation->isComplete())
                <a href="{{ route('hr.evaluations.print', $evaluation) }}" target="_blank"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print Rubric Sheet
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @unless ($evaluation->isComplete())
                {{-- Email Dispatch & Share Link Card --}}
                <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg shadow-sm">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-lg font-semibold text-primary-900">Send to Immediate Supervisor</h3>
                            @if ($evaluation->invitation_sent_at)
                                <span class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full font-medium">
                                    Last dispatched {{ $evaluation->invitation_sent_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 mb-4">You can dispatch an official invitation email directly to the supervisor, or copy the secure single-use evaluation token link below.</p>

                        {{-- Email Dispatch Form --}}
                        <form method="POST" action="{{ route('hr.evaluations.send-invitation', $evaluation) }}" class="p-4 bg-gray-50 border border-gray-200 rounded-lg mb-5 space-y-3">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700">Supervisor Name</label>
                                    <input type="text" name="supervisor_name" value="{{ old('supervisor_name', $evaluation->supervisor_name) }}" required
                                           placeholder="e.g. John Doe (Dept Manager)"
                                           class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700">Supervisor Email Address</label>
                                    <input type="email" name="supervisor_email" value="{{ old('supervisor_email', $evaluation->supervisor_email) }}" required
                                           placeholder="supervisor@company.com"
                                           class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700">Optional Note for Supervisor</label>
                                <input type="text" name="custom_note" placeholder="e.g. Kindly submit this before Friday for the intern's mid-term clearance."
                                       class="mt-1 block w-full text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            </div>
                            <div class="flex justify-end pt-1">
                                <button type="submit" class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-wider hover:bg-primary-700 transition flex items-center gap-1.5 shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                    {{ $evaluation->invitation_sent_at ? 'Resend Invitation Email' : 'Send Invitation Email' }}
                                </button>
                            </div>
                        </form>

                        {{-- Manual Copy Link --}}
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Direct Evaluation URL</label>
                        <div class="flex items-center gap-2">
                            <input type="text" readonly value="{{ route('evaluations.show', $evaluation->token) }}"
                                class="flex-1 border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm" id="eval-link" />
                            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('eval-link').value); this.textContent='Copied!'"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest transition">
                                Copy
                            </button>
                        </div>
                    </div>
                </div>
            @endunless

            <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg shadow-sm">
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

                        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-400">
                            <span>Submitted {{ $evaluation->submitted_at?->format('M d, Y g:i A') }}</span>
                            <a href="{{ route('hr.evaluations.print', $evaluation) }}" target="_blank" class="text-primary-600 hover:underline font-semibold">
                                View Official Printable Report &rarr;
                            </a>
                        </div>
                    @else
                        <p class="mt-6 text-gray-500">This evaluation has not been submitted yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
