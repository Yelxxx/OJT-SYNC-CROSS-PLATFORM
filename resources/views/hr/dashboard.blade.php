<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('HR Dashboard') }} — {{ $company->name }}
        </h2>
    </x-slot>

    @php
        $hiredRate = $stats['applications'] > 0 ? (int) round(($stats['hired'] / $stats['applications']) * 100) : 0;
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Stat cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('hr.jobs.index') }}" class="block rounded-xl transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-400">
                    <x-stat-card label="Total Job Posts" :value="$stats['jobs']" icon="briefcase" tone="primary" />
                </a>
                <a href="{{ route('hr.jobs.index', ['status' => 'open']) }}" class="block rounded-xl transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-400">
                    <x-stat-card label="Open Positions" :value="$stats['openJobs']" icon="bolt" tone="gold" />
                </a>
                <a href="{{ route('hr.applications.index') }}" class="block rounded-xl transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-400">
                    <x-stat-card label="Applications" :value="$stats['applications']" icon="document-text" tone="primary" />
                </a>
                <a href="{{ route('hr.interns.index') }}" class="block rounded-xl transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-400">
                    <x-stat-card label="Hired Interns" :value="$stats['hired']" icon="users" tone="green" />
                </a>
                <a href="{{ route('hr.applications.index', ['status' => 'pending']) }}" class="block rounded-xl transition hover:-translate-y-0.5 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-primary-400">
                    <x-stat-card label="Awaiting Review" :value="$stats['pendingReview']" icon="clock" tone="gold" />
                </a>
            </div>

            {{-- Recent applications + quick actions --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <x-card flush class="lg:col-span-2">
                    <div class="p-6">
                        <x-section-header title="Recent Applications">
                            <x-slot name="action">
                                <a href="{{ route('hr.applications.index') }}" class="inline-flex items-center px-2 py-1 text-sm font-medium text-primary-600 hover:text-primary-800 hover:bg-primary-50 rounded-md transition">
                                    View all →
                                </a>
                            </x-slot>
                        </x-section-header>

                        <div class="mt-4 space-y-3">
                            @forelse ($recentApplications as $application)
                                <div class="flex items-center justify-between gap-4 border border-primary-100 rounded-lg p-4 hover:border-primary-200 hover:bg-primary-50/60 transition">
                                    <div class="min-w-0">
                                        <p class="font-medium text-gray-900">{{ $application->student->user->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $application->student->program ?? '—' }} · {{ $application->jobPosting->title }}</p>
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $application->applied_at?->diffForHumans() }}</p>
                                    </div>
                                    <div class="shrink-0 flex items-center gap-2">
                                        <x-status-badge :status="$application->status" />
                                        <a href="{{ route('hr.applications.index', ['job' => $application->job_posting_id]) }}" class="text-xs font-medium text-primary-600 hover:underline">Manage</a>
                                    </div>
                                </div>
                            @empty
                                <x-empty-state icon="inbox" title="No applications yet" message="Applications from students will appear here once they start applying." />
                            @endforelse
                        </div>
                    </div>
                </x-card>

                <div class="space-y-6">
                    <x-card>
                        <x-section-header title="Quick Actions" />
                        <div class="mt-4 space-y-3">
                            <a href="{{ route('hr.jobs.create') }}" class="flex items-center gap-3 p-3 rounded-lg border border-primary-100 hover:border-primary-300 hover:bg-primary-50/60 transition group">
                                <span class="h-9 w-9 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center group-hover:bg-primary-200 transition">
                                    <x-icon name="plus" class="h-5 w-5" />
                                </span>
                                <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700 transition">Post a new internship</span>
                            </a>
                            <a href="{{ route('hr.jobs.index') }}" class="flex items-center gap-3 p-3 rounded-lg border border-primary-100 hover:border-gold-300 hover:bg-gold-50/60 transition group">
                                <span class="h-9 w-9 rounded-lg bg-gold-100 text-gold-600 flex items-center justify-center group-hover:bg-gold-200 transition">
                                    <x-icon name="briefcase" class="h-5 w-5" />
                                </span>
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gold-700 transition">Manage job postings</span>
                            </a>
                            <a href="{{ route('hr.applications.index') }}" class="flex items-center gap-3 p-3 rounded-lg border border-primary-100 hover:border-primary-300 hover:bg-primary-50/60 transition group">
                                <span class="h-9 w-9 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center group-hover:bg-primary-200 transition">
                                    <x-icon name="document-text" class="h-5 w-5" />
                                </span>
                                <span class="text-sm font-medium text-gray-700 group-hover:text-primary-700 transition">Review applications</span>
                            </a>
                            <a href="{{ route('hr.dtr.index') }}" class="flex items-center gap-3 p-3 rounded-lg border border-primary-100 hover:border-gold-300 hover:bg-gold-50/60 transition group">
                                <span class="h-9 w-9 rounded-lg bg-gold-100 text-gold-600 flex items-center justify-center group-hover:bg-gold-200 transition">
                                    <x-icon name="clock" class="h-5 w-5" />
                                </span>
                                <span class="text-sm font-medium text-gray-700 group-hover:text-gold-700 transition">Verify time records</span>
                            </a>
                        </div>
                    </x-card>

                    <x-card>
                        <x-section-header title="Company Profile" />
                        <p class="mt-4 text-sm text-gray-600">{{ $company->description }}</p>
                        <div class="mt-4 space-y-1.5 text-xs text-gray-500">
                            <p class="flex items-center gap-1.5"><x-icon name="office-building" class="h-4 w-4 text-gray-400" /> {{ $company->address }}</p>
                            <p class="flex items-center gap-1.5"><x-icon name="document-text" class="h-4 w-4 text-gray-400" /> {{ $company->contact_email }}</p>
                        </div>
                    </x-card>
                </div>
            </div>

            {{-- Charts --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <x-card>
                    <x-section-header title="Application Status" subtitle="Hiring funnel at a glance" />
                    <div class="flex flex-col items-center py-6">
                        <x-donut-chart :percent="$hiredRate" center="{{ $hiredRate }}%" caption="hired rate" />
                    </div>
                    @if ($applicationStatusChart->isEmpty())
                        <p class="py-6 text-center text-sm text-gray-500">No applications yet.</p>
                    @else
                        <div class="mt-2 flex flex-wrap justify-center gap-3">
                            @foreach ($applicationStatusChart as $item)
                                <div class="flex items-center gap-2.5 rounded-lg border border-primary-100 bg-cream-50 px-3.5 py-2">
                                    <x-status-badge :status="$item['status']" />
                                    <span class="font-bold leading-none text-primary-900">{{ $item['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <x-card>
                    <x-section-header title="Applications by Job Posting" subtitle="Most applied-to positions" />
                    <div class="mt-4">
                        <x-bars-chart :items="$applicationsPerJob" />
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
