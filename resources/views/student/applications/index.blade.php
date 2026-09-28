<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('My Applications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if ($applications->isEmpty())
                <x-card>
                    <x-empty-state icon="inbox" title="No applications yet" message="You haven't applied to any internship yet. Browse open positions to get started.">
                        <x-slot name="action">
                            <x-button href="{{ route('student.jobs.index') }}">
                                Browse internships
                            </x-button>
                        </x-slot>
                    </x-empty-state>
                </x-card>
            @else
                <x-card flush>
                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach ($applications as $application)
                                <div class="flex items-center justify-between gap-4 border border-primary-100 rounded-lg p-4 hover:border-primary-200 hover:bg-primary-50/60 transition">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-gray-900">{{ $application->jobPosting->title }}</p>
                                        <p class="text-sm text-gray-500">{{ $application->jobPosting->company->name }}</p>
                                        <p class="mt-1 text-xs text-gray-400">Applied {{ $application->applied_at?->format('M d, Y g:i A') }}</p>
                                        @if ($application->status === \App\Models\Application::STATUS_INTERVIEW && $application->interview_at)
                                            <p class="mt-2 text-sm font-medium text-primary-800">
                                                🗓 Interview scheduled: {{ $application->interview_at->isoFormat('ddd, MMM D, YYYY [at] g:i A') }}
                                            </p>
                                        @endif
                                        @if ($application->status === \App\Models\Application::STATUS_REJECTED && $application->rejection_reason)
                                            <p class="mt-2 text-xs text-gray-500">Reason: {{ $application->rejection_reason }}</p>
                                        @endif
                                        @if ($application->resume_path)
                                            <p class="mt-1 text-xs text-gray-500">
                                                📎 <a href="{{ asset('storage/'.$application->resume_path) }}" target="_blank" class="text-primary-600 hover:underline">View resume</a>
                                            </p>
                                        @endif
                                    </div>
                                    <div class="shrink-0 flex items-center gap-3">
                                        <x-status-badge :status="$application->status" />
                                        @if ($application->status === \App\Models\Application::STATUS_PENDING)
                                            <form method="POST" action="{{ route('student.applications.destroy', $application) }}" onsubmit="return confirm('Withdraw this application? You can apply again later.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700 hover:underline">Withdraw</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
