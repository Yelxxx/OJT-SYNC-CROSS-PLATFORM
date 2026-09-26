<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Job Postings') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <p class="text-sm text-gray-500">Manage the internship positions your company offers.</p>
                <x-button href="{{ route('hr.jobs.create') }}">
                    <x-icon name="plus" class="h-4 w-4" />
                    Post New Job
                </x-button>
            </div>

            {{-- Filters --}}
            <div class="bg-white rounded-xl border border-primary-100 shadow-sm p-4 mb-6">
                <form method="GET" action="{{ route('hr.jobs.index') }}" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[220px]">
                        <x-input-label for="q" :value="__('Search')" />
                        <x-text-input id="q" name="q" type="text" value="{{ $filters['q'] }}" placeholder="Search by title..." class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="status" :value="__('Status')" />
                        <select id="status" name="status" class="mt-1 block border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-sm">
                            <option value="">All statuses</option>
                            <option value="open" @selected($filters['status'] === 'open')>Open</option>
                            <option value="closed" @selected($filters['status'] === 'closed')>Closed</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 transition">
                        Search
                    </button>
                    @if ($filters['q'] || $filters['status'])
                        <a href="{{ route('hr.jobs.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Clear</a>
                    @endif
                </form>
            </div>

            @if ($jobs->isEmpty())
                <x-card>
                    <x-empty-state icon="search" title="No job postings found" message="No job postings match your filters — try different keywords or clear the filters." />
                </x-card>
            @else
                <x-card flush>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 bg-cream-100">
                                    <th class="px-4 py-3">Position</th>
                                    <th class="px-4 py-3">Slots</th>
                                    <th class="px-4 py-3">Applications</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Posted</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($jobs as $job)
                                    <tr class="hover:bg-primary-50 transition">
                                        <td class="px-4 py-4">
                                            <p class="font-medium text-gray-900">{{ $job->title }}</p>
                                            <p class="text-xs text-gray-500 max-w-xs truncate">{{ $job->description }}</p>
                                        </td>
                                        <td class="px-4 py-4 text-gray-600">{{ $job->slots }}</td>
                                        <td class="px-4 py-4">
                                            <a href="{{ route('hr.applications.index', ['job' => $job->id]) }}" class="font-medium text-primary-600 hover:underline">{{ $job->applications_count }}</a>
                                        </td>
                                        <td class="px-4 py-4"><x-status-badge :status="$job->status" /></td>
                                        <td class="px-4 py-4 text-gray-500">{{ $job->created_at->format('M d, Y') }}</td>
                                        <td class="px-4 py-4">
                                            <div class="flex justify-end gap-2">
                                                <a href="{{ route('hr.jobs.qr', $job) }}" title="Share via QR code" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary-600 bg-primary-50 rounded-md hover:bg-primary-100 transition">
                                                    QR
                                                </a>
                                                <a href="{{ route('hr.jobs.edit', $job) }}" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary-600 bg-primary-50 rounded-md hover:bg-primary-100 transition">
                                                    Edit
                                                </a>
                                                <form method="POST" action="{{ route('hr.jobs.destroy', $job) }}" onsubmit="return confirm('Delete this job posting? This will also remove its applications.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-rose-600 bg-rose-50 rounded-md hover:bg-rose-100 transition">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
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
