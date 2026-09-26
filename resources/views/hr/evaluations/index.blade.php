<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Evaluations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600">Generate evaluation links for hired students.</p>
                <x-button href="{{ route('hr.evaluations.create') }}">
                    + New Evaluation
                </x-button>
            </div>

            @if ($evaluations->isEmpty())
                <x-card>
                    <x-empty-state icon="document-text" title="No evaluations yet" message="Generate an evaluation link for a hired student to get started." />
                </x-card>
            @else
                <x-card flush>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 bg-cream-100">
                                    <th class="px-4 py-3">Student</th>
                                    <th class="px-4 py-3">Position</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Score</th>
                                    <th class="px-4 py-3">Submitted</th>
                                    <th class="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($evaluations as $evaluation)
                                    <tr class="hover:bg-primary-50 transition">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $evaluation->student->user->name }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $evaluation->jobPosting?->title ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            @if ($evaluation->isComplete())
                                                <x-status-badge status="completed" />
                                            @else
                                                <x-status-badge status="pending" />
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 font-semibold text-primary-700">{{ $evaluation->overall_score ?? '—' }} / 5</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $evaluation->submitted_at?->format('M d, Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <a href="{{ route('hr.evaluations.show', $evaluation) }}" class="text-xs font-medium text-primary-600 hover:underline">View</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>

                <div class="mt-6">
                    {{ $evaluations->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
