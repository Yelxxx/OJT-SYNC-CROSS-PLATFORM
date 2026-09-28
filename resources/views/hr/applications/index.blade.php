<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Applications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Filters --}}
            <div class="bg-white rounded-xl border border-primary-100 shadow-sm p-4 mb-6">
                <form method="GET" action="{{ route('hr.applications.index') }}" class="flex flex-wrap items-end gap-4">
                    <div>
                        <x-input-label for="job" :value="__('Job Posting')" />
                        <select id="job" name="job" class="mt-1 block border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                            <option value="">All jobs</option>
                            @foreach ($jobs as $job)
                                <option value="{{ $job->id }}" @selected((string) $filters['job'] === (string) $job->id)>{{ $job->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                            <option value="">All statuses</option>
                            @foreach (\App\Models\Application::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                        Filter
                    </button>
                    @if ($filters['job'] || $filters['status'])
                        <a href="{{ route('hr.applications.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Clear</a>
                    @endif
                </form>
            </div>

            @if ($applications->isEmpty())
                <x-card>
                    <x-empty-state icon="inbox" title="No applications found" message="No applications match your filters — try adjusting the filters or check back later." />
                </x-card>
            @else
                <x-card flush>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 bg-cream-100">
                                    <th class="px-4 py-3">Applicant</th>
                                    <th class="px-4 py-3">Position</th>
                                    <th class="px-4 py-3">Resume</th>
                                    <th class="px-4 py-3">Applied</th>
                                    <th class="px-4 py-3">Current Status</th>
                                    <th class="px-4 py-3">Update Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($applications as $application)
                                    <tr class="hover:bg-primary-50 transition">
                                        <td class="px-4 py-4">
                                            <a href="{{ route('hr.applications.show', $application) }}" class="font-medium text-gray-900 hover:text-primary-600 hover:underline">{{ $application->student->user->name }}</a>
                                            <p class="text-xs text-gray-500">{{ $application->student->program ?? '—' }} · {{ $application->student->year_level ?? '—' }}</p>
                                            @if ($application->cover_letter)
                                                <details class="mt-1 text-xs text-gray-500">
                                                    <summary class="cursor-pointer text-primary-600 hover:underline">Cover letter</summary>
                                                    <p class="mt-1 text-gray-600 whitespace-pre-line">{{ $application->cover_letter }}</p>
                                                </details>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-gray-600">{{ $application->jobPosting->title }}</td>
                                        <td class="px-4 py-4">
                                            @if ($application->resume_path)
                                                <a href="{{ route('applications.resume', $application) }}" class="text-xs font-medium text-primary-600 hover:underline">View ↓</a>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-gray-500">{{ $application->applied_at?->format('M d, Y') }}</td>
                                        <td class="px-4 py-4">
                                            <x-status-badge :status="$application->status" />
                                            @if ($application->status === \App\Models\Application::STATUS_INTERVIEW && $application->interview_at)
                                                <p class="mt-1 text-xs font-medium text-primary-700">🗓 {{ $application->interview_at->format('M d, g:i A') }}</p>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            {{-- Quick status update. Moving to interview requires
                                            a date and to rejected a reason (server-side
                                            required_if) — the extra inputs appear inline and
                                            disabled inputs are never submitted. --}}
                                            <form method="POST" action="{{ route('hr.applications.update', $application) }}"
                                                  x-data="{ status: '{{ $application->status }}' }" class="space-y-2">
                                                @csrf
                                                @method('PATCH')
                                                <select name="status" x-model="status" class="border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-xs">
                                                    @foreach (\App\Models\Application::STATUSES as $value => $label)
                                                        <option value="{{ $value }}" @selected($application->status === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>

                                                <div x-show="status === 'interview'" x-cloak>
                                                    <input type="datetime-local" name="interview_at" x-bind:disabled="status !== 'interview'"
                                                           value="{{ old('interview_at', $application->interview_at?->format('Y-m-d\\TH:i')) }}"
                                                           class="border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-xs w-44">
                                                </div>

                                                <div x-show="status === 'rejected'" x-cloak>
                                                    <input type="text" name="rejection_reason" x-bind:disabled="status !== 'rejected'"
                                                           placeholder="Reason (shared with student)"
                                                           class="border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-xs w-44">
                                                </div>

                                                <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 transition">
                                                    Save
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <div class="mt-6">
                    {{ $applications->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
