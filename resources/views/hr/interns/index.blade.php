<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            My Interns
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            {{-- Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                <x-card>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Current interns</p>
                    <p class="text-2xl font-bold text-primary-900 mt-1">{{ $stats['interns'] }}</p>
                </x-card>
                <x-card>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Hours completed</p>
                    <p class="text-2xl font-bold text-primary-900 mt-1">{{ $stats['completed'] }}</p>
                </x-card>
                <x-card>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500">Awaiting evaluation</p>
                    <p class="text-2xl font-bold text-primary-900 mt-1">{{ $stats['awaitingEvaluation'] }}</p>
                </x-card>
            </div>

            @if ($interns->isEmpty())
                <x-card>
                    <x-empty-state icon="inbox" title="No interns yet" message="Students you hire will appear here so you can track their OJT hours and evaluations." />
                </x-card>
            @else
                <x-card flush>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 bg-cream-100">
                                    <th class="px-4 py-3">Intern</th>
                                    <th class="px-4 py-3">Position</th>
                                    <th class="px-4 py-3">Hired on</th>
                                    <th class="px-4 py-3">Hours progress</th>
                                    <th class="px-4 py-3">Onboarding</th>
                                    <th class="px-4 py-3">Evaluation</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($interns as $intern)
                                    <tr class="hover:bg-primary-50 transition">
                                        <td class="px-4 py-4">
                                            <p class="font-medium text-gray-900">{{ $intern['student']->user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $intern['student']->program ?? '—' }} · {{ $intern['student']->student_no }}</p>
                                        </td>
                                        <td class="px-4 py-4 text-gray-600">{{ $intern['application']->jobPosting->title }}</td>
                                        <td class="px-4 py-4 text-gray-500">{{ $intern['application']->applied_at?->format('M d, Y') }}</td>
                                        <td class="px-4 py-4 w-56">
                                            @php
                                                $percent = min(100, $intern['progressPercent']);
                                                $hours = $intern['totalHours'];
                                                $required = $intern['student']->required_hours;
                                            @endphp
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full {{ $percent >= 100 ? 'bg-primary-600' : 'bg-gold-400' }}"
                                                         style="width: {{ $percent }}%"></div>
                                                </div>
                                                <span class="text-xs font-medium text-gray-600 whitespace-nowrap">
                                                    {{ number_format($hours, 1) }} / {{ $required }} h
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4">
                                            @if ($intern['checklistTotal'] === 0)
                                                <span class="text-xs text-gray-400">—</span>
                                            @else
                                                <details>
                                                    <summary class="cursor-pointer text-xs font-medium {{ $intern['checklistDone'] === $intern['checklistTotal'] ? 'text-emerald-700' : 'text-gold-700' }}">
                                                        {{ $intern['checklistDone'] }}/{{ $intern['checklistTotal'] }} done
                                                    </summary>
                                                    <ul class="mt-2 space-y-1.5 w-56">
                                                        @foreach ($intern['student']->checklistItems as $item)
                                                            <li>
                                                                <form method="POST" action="{{ route('hr.interns.checklist.toggle', $item) }}">
                                                                    @csrf
                                                                    <button type="submit" class="flex items-start gap-2 text-left text-xs hover:opacity-75 transition">
                                                                        <span class="shrink-0 mt-0.5 inline-flex items-center justify-center h-4 w-4 rounded border {{ $item->is_complete ? 'bg-emerald-500 border-emerald-500 text-white' : 'border-gray-300 bg-white' }}">
                                                                            @if ($item->is_complete)<x-icon name="check-circle" class="h-4 w-4 text-emerald-600" />@endif
                                                                        </span>
                                                                        <span class="{{ $item->is_complete ? 'text-gray-400 line-through' : 'text-gray-700' }}">{{ $item->title }}</span>
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                    <form method="POST" action="{{ route('hr.interns.checklist.add', $intern['student']) }}" class="mt-2 flex gap-1">
                                                        @csrf
                                                        <input type="text" name="title" placeholder="Add requirement…" required
                                                               class="w-full text-xs border-gray-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm">
                                                        <button class="shrink-0 px-2 py-1 text-xs font-semibold text-primary-600 hover:bg-primary-50 rounded-md transition">+</button>
                                                    </form>
                                                </details>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4">
                                            @if ($intern['evaluationComplete'])
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-100 text-primary-800 ring-1 ring-inset ring-primary-300">Submitted</span>
                                            @else
                                                <a href="{{ route('hr.evaluations.create') }}"
                                                   class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gold-100 text-gold-800 ring-1 ring-inset ring-gold-300 hover:bg-gold-200 transition">
                                                    Request evaluation →
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
