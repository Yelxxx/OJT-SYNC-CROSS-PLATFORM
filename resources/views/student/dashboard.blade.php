<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Student Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Welcome + OJT status --}}
            <x-card>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Welcome back,</p>
                        <h3 class="text-2xl font-bold text-primary-900">{{ Auth::user()->name }}</h3>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ $student->student_no }} · {{ $student->program ?? 'Program TBD' }} · {{ $student->year_level ?? '—' }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-400">OJT Company</p>
                        <p class="font-semibold text-primary-900">{{ $student->company?->name ?? 'Not yet assigned' }}</p>
                        <p class="text-sm text-gray-500">{{ $student->company?->address }}</p>
                    </div>
                </div>
            </x-card>

            {{-- Hours progress + application status --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <x-card>
                    <x-section-header title="OJT Hours Progress" subtitle="{{ $totalHours }} of {{ $student->required_hours }} hours completed" />
                    <div class="flex flex-col items-center py-6">
                        <x-donut-chart :percent="$student->hoursProgressPercent()" center="{{ $student->hoursProgressPercent() }}%" caption="of {{ $student->required_hours }} hrs" />
                    </div>
                    <div class="mt-4 flex items-center justify-center gap-2 text-xs text-gray-500">
                        <x-icon name="clock" class="h-4 w-4 text-gold-500" />
                        <span><span class="font-semibold text-primary-900">{{ number_format($totalHours, 1) }}</span> hours logged so far</span>
                    </div>
                </x-card>

                <x-card class="lg:col-span-2">
                    <x-section-header title="Application Status" subtitle="Where your applications currently stand" />
                    @if ($applicationStatusChart->isEmpty())
                        <p class="py-10 text-center text-sm text-gray-500">No applications yet — your status will appear here once you apply.</p>
                    @else
                        <div class="flex flex-wrap gap-3 pt-2">
                            @foreach ($applicationStatusChart as $item)
                                <div class="flex items-center gap-2.5 rounded-lg border border-primary-100 bg-cream-50 px-4 py-2.5">
                                    <x-status-badge :status="$item['status']" />
                                    <span class="text-lg font-bold leading-none text-primary-900">{{ $item['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- Applications --}}
            <x-card flush>
                <div class="p-6">
                    <x-section-header title="My Applications">
                        <x-slot name="action">
                            <a href="{{ route('student.jobs.index') }}" class="inline-flex items-center px-2 py-1 text-sm font-medium text-primary-600 hover:text-primary-800 hover:bg-primary-50 rounded-md transition">
                                Browse more jobs →
                            </a>
                        </x-slot>
                    </x-section-header>

                    @if ($applications->isEmpty())
                        <x-empty-state icon="briefcase" title="No applications yet" message="You haven't applied to any internship yet. Browse open positions to get started.">
                            <x-slot name="action">
                                <x-button href="{{ route('student.jobs.index') }}">
                                    Browse open internships
                                </x-button>
                            </x-slot>
                        </x-empty-state>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                        <th class="px-4 py-3">Position</th>
                                        <th class="px-4 py-3">Company</th>
                                        <th class="px-4 py-3">Applied</th>
                                        <th class="px-4 py-3">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($applications as $application)
                                        <tr class="hover:bg-primary-50 transition">
                                            <td class="px-4 py-3 font-medium text-gray-900">{{ $application->jobPosting->title }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $application->jobPosting->company->name }}</td>
                                            <td class="px-4 py-3 text-gray-500">{{ $application->applied_at?->format('M d, Y') }}</td>
                                            <td class="px-4 py-3"><x-status-badge :status="$application->status" /></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
