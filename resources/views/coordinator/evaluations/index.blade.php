<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Student Evaluations') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Search --}}
            <div class="bg-white rounded-xl border border-primary-100 shadow-sm p-4">
                <form method="GET" action="{{ route('coordinator.evaluations.index') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <x-input-label for="q" :value="__('Search student')" />
                        <x-text-input id="q" name="q" type="text" value="{{ $q }}" placeholder="e.g. Peter Parker" class="mt-1 block w-full" />
                    </div>
                    <button type="submit" class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                        Search
                    </button>
                    @if ($q)
                        <a href="{{ route('coordinator.evaluations.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Clear</a>
                    @endif
                </form>
            </div>

            {{-- Evaluations --}}
            <div class="bg-white overflow-hidden border border-primary-100 sm:rounded-lg">
                @if ($evaluations->isEmpty())
                    <x-empty-state icon="document" title="No evaluations found" message="Employer evaluations of your students will appear here once submitted." />
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 bg-cream-100">
                                    <th class="px-4 py-3">Student</th>
                                    <th class="px-4 py-3">Company</th>
                                    <th class="px-4 py-3">Position</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Score</th>
                                    <th class="px-4 py-3">Submitted</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($evaluations as $evaluation)
                                    <tr class="hover:bg-primary-50 transition">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $evaluation->student->user->name }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $evaluation->jobPosting?->company?->name ?? '—' }}</td>
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="mt-6">
                {{ $evaluations->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
