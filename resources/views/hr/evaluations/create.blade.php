<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('New Evaluation') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                <div class="p-6">
                    @if ($hired->isEmpty())
                        <x-empty-state icon="users" title="No hired students yet" message="No hired students to evaluate yet. Mark an application as Hired first.">
                            <x-slot name="action">
                                <x-button href="{{ route('hr.applications.index') }}">
                                    Go to Applications
                                </x-button>
                            </x-slot>
                        </x-empty-state>
                    @else
                        <form method="POST" action="{{ route('hr.evaluations.store') }}" class="space-y-6">
                            @csrf

                            <div>
                                <x-input-label for="student_id" :value="__('Student')" />
                                <select id="student_id" name="student_id" required class="mt-1 block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                                    @foreach ($hired as $application)
                                        <option value="{{ $application->student_id }}">
                                            {{ $application->student->user->name }} — {{ $application->jobPosting->title }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="job_posting_id" :value="__('Position')" />
                                <select id="job_posting_id" name="job_posting_id" required class="mt-1 block w-full border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                                    @foreach ($hired as $application)
                                        <option value="{{ $application->job_posting_id }}">
                                            {{ $application->jobPosting->title }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('job_posting_id')" class="mt-2" />
                            </div>

                            <div class="bg-cream-100 border border-primary-100 rounded-lg p-4">
                                <p class="text-sm text-gray-600">
                                    A unique link will be generated. Share it with the student's supervisor to complete the
                                    evaluation (ratings: punctuality, quality of work, initiative, communication — 1 to 5).
                                </p>
                            </div>

                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('hr.evaluations.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</a>
                                <x-primary-button>
                                    {{ __('Generate Link') }}
                                </x-primary-button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
