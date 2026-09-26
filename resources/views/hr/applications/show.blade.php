<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-primary-900 leading-tight">
                Application — {{ $application->student->user->name }}
            </h2>
            <a href="{{ route('hr.applications.index') }}" class="text-sm font-medium text-primary-600 hover:underline">← Back to applications</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Left: applicant details --}}
                <div class="lg:col-span-2 space-y-6">
                    <x-card>
                        <div class="flex items-start justify-between gap-4 flex-wrap">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $application->student->user->name }}</h3>
                                <p class="text-sm text-gray-500">
                                    {{ $application->student->program ?? '—' }} · {{ $application->student->year_level ?? '—' }}
                                    · {{ $application->student->student_no }}
                                </p>
                                <p class="text-sm text-gray-600 mt-1">
                                    Applied for <span class="font-medium">{{ $application->jobPosting->title }}</span>
                                    on {{ $application->applied_at?->format('M d, Y') }}
                                </p>
                            </div>
                            <x-status-badge :status="$application->status" />
                        </div>

                        @if ($application->interview_at)
                            <div class="mt-4 p-3 rounded-lg bg-primary-50 border border-primary-100 text-sm text-primary-900">
                                🗓 Interview scheduled:
                                <span class="font-semibold">{{ $application->interview_at->format('M d, Y g:i A') }}</span>
                            </div>
                        @endif

                        @if ($application->status === \App\Models\Application::STATUS_REJECTED && $application->rejection_reason)
                            <div class="mt-4 p-3 rounded-lg bg-red-50 border border-red-100 text-sm text-red-800">
                                <span class="font-semibold">Rejection reason:</span> {{ $application->rejection_reason }}
                            </div>
                        @endif

                        <div class="mt-6 space-y-4">
                            <div>
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">Cover letter</h4>
                                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $application->cover_letter ?? '— No cover letter provided —' }}</p>
                            </div>

                            <div>
                                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-2">Resume</h4>
                                @if ($application->resume_path)
                                    <a href="{{ route('applications.resume', $application) }}"
                                       class="inline-flex items-center gap-2 text-sm font-medium text-primary-600 hover:underline">
                                        ⬇ Download resume
                                    </a>
                                @else
                                    <span class="text-sm text-gray-400">— No resume uploaded —</span>
                                @endif
                            </div>
                        </div>
                    </x-card>

                    {{-- Update status --}}
                    <x-card>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Update status</h3>
                        <form method="POST" action="{{ route('hr.applications.update', $application) }}" class="space-y-4"
                              x-data="{ status: '{{ $application->status }}' }">
                            @csrf
                            @method('PATCH')

                            <div class="flex flex-wrap items-end gap-4">
                                <div>
                                    <x-input-label for="status" :value="__('Status')" />
                                    <select id="status" name="status" x-model="status"
                                            class="mt-1 block border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                                        @foreach (\App\Models\Application::STATUSES as $value => $label)
                                            <option value="{{ $value }}" @selected($application->status === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div x-show="status === 'interview'" class="w-64">
                                    <x-input-label for="interview_at" :value="__('Interview date & time')" />
                                    <input type="datetime-local" id="interview_at" name="interview_at"
                                           value="{{ $application->interview_at?->format('Y-m-d\TH:i') }}"
                                           class="mt-1 block border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm w-full">
                                    @error('interview_at') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <div x-show="status === 'rejected'" class="w-full max-w-md">
                                    <x-input-label for="rejection_reason" :value="__('Rejection reason (shared with the student)')" />
                                    <textarea id="rejection_reason" name="rejection_reason" rows="3"
                                              class="mt-1 block border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm w-full"
                                              placeholder="e.g. We moved forward with candidates whose availability matched the schedule.">{{ old('rejection_reason', $application->rejection_reason) }}</textarea>
                                    @error('rejection_reason') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                </div>

                                <button type="submit"
                                        class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                                    Save
                                </button>
                            </div>
                        </form>
                    </x-card>

                    {{-- Notes --}}
                    <x-card>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Internal notes</h3>
                        <form method="POST" action="{{ route('hr.applications.notes.store', $application) }}" class="mb-6">
                            @csrf
                            <textarea name="body" rows="2" required
                                      class="block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm"
                                      placeholder="Add a note for your team (e.g. 'Great portfolio — schedule interview Thursday')"></textarea>
                            @error('body') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            <button type="submit"
                                    class="mt-2 px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                                Add note
                            </button>
                        </form>

                        @if ($application->notes->isEmpty())
                            <p class="text-sm text-gray-400">No notes yet.</p>
                        @else
                            <ul class="space-y-3">
                                @foreach ($application->notes as $note)
                                    <li class="p-3 rounded-lg bg-cream-50 border border-primary-50">
                                        <p class="text-sm text-gray-700 whitespace-pre-line">{{ $note->body }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ $note->user->name }} · {{ $note->created_at->format('M d, Y g:i A') }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </x-card>
                </div>

                {{-- Right: status history timeline --}}
                <div>
                    <x-card>
                        <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500 mb-4">Status history</h3>
                        <ol class="relative border-s border-primary-100 ms-3 space-y-6">
                            <li class="ms-6">
                                <span class="absolute flex items-center justify-center w-3 h-3 bg-primary-200 rounded-full -start-1.5 ring-4 ring-white"></span>
                                <p class="text-sm font-medium text-gray-900">Applied</p>
                                <p class="text-xs text-gray-400">{{ $application->applied_at?->format('M d, Y g:i A') }}</p>
                            </li>
                            @foreach ($application->statusHistory as $history)
                                <li class="ms-6">
                                    <span class="absolute flex items-center justify-center w-3 h-3 rounded-full -start-1.5 ring-4 ring-white {{ $history->to_status === 'rejected' ? 'bg-red-400' : 'bg-primary-600' }}"></span>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ \App\Models\Application::STATUSES[$history->to_status] ?? $history->to_status }}
                                    </p>
                                    @if ($history->from_status)
                                        <p class="text-xs text-gray-400">
                                            from {{ \App\Models\Application::STATUSES[$history->from_status] ?? $history->from_status }}
                                            by {{ $history->user?->name ?? 'System' }}
                                        </p>
                                    @endif
                                    @if ($history->note)
                                        <p class="text-xs text-gray-500 mt-1">{{ $history->note }}</p>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $history->created_at->format('M d, Y g:i A') }}</p>
                                </li>
                            @endforeach
                        </ol>
                    </x-card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
