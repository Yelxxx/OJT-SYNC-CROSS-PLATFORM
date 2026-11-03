<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-primary-900 leading-tight">
            {{ __('Coordinator Command Center') }}
        </h2>
    </x-slot>

    @php
        $activeRate = $stats['students'] > 0 ? (int) round(($stats['activeStudents'] / $stats['students']) * 100) : 0;
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Stat cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-stat-card label="Total Students" :value="$stats['students']" icon="academic-cap" tone="primary" />
                <x-stat-card label="Active OJT" :value="$stats['activeStudents']" icon="check-circle" tone="green" />
                <x-stat-card label="Placed in Companies" :value="$stats['placedStudents']" icon="office-building" tone="gold" />
                <x-stat-card label="Total Applications" :value="$stats['applications']" icon="document-text" tone="primary" />
            </div>

            {{-- Students overview --}}
            <x-card flush>
                <div class="p-6">
                    <x-section-header title="All Students & OJT Progress" />
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3">Student</th>
                                    <th class="px-4 py-3">Student No.</th>
                                    <th class="px-4 py-3">Program</th>
                                    <th class="px-4 py-3">Hired Company</th>
                                    <th class="px-4 py-3">Hours</th>
                                    <th class="px-4 py-3">OJT Status</th>
                                    <th class="px-4 py-3">Faculty Endorsement</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($students as $student)
                                    <tr class="hover:bg-primary-50 transition">
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-gray-900">{{ $student->user->name }}</p>
                                            <p class="text-xs text-gray-500">{{ $student->user->email }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600">{{ $student->student_no }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $student->program ?? '—' }} · {{ $student->year_level ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600">
                                            @if ($student->company)
                                                <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-800 bg-green-50 border border-green-200 px-2 py-0.5 rounded">
                                                    {{ $student->company->name }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Not placed yet</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-24 bg-primary-100 rounded-full h-2 overflow-hidden">
                                                    <div class="bg-primary-600 h-2 rounded-full" style="width: {{ min(100, $student->hoursProgressPercent()) }}%"></div>
                                                </div>
                                                <span class="text-xs font-medium text-gray-700">{{ $student->totalHours() }}/{{ $student->required_hours }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-status-badge :status="$student->status === 'active' && ! $student->company_id ? 'pending' : $student->status" />
                                        </td>
                                        <td class="px-4 py-3">
                                            <form method="POST" action="{{ route('coordinator.students.update', $student) }}" class="flex flex-col gap-1.5">
                                                @csrf
                                                @method('PATCH')
                                                <div class="flex items-center gap-1.5">
                                                    <select name="endorsed_company_id" class="border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-xs max-w-[150px]">
                                                        <option value="">No Endorsement</option>
                                                        @foreach ($companies as $company)
                                                            <option value="{{ $company->id }}" @selected($student->endorsed_company_id === $company->id)>{{ $company->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="inline-flex items-center px-2 py-1 text-xs font-semibold text-white bg-primary-600 rounded-md hover:bg-primary-700 transition">
                                                        Save
                                                    </button>
                                                </div>
                                                <input type="text" name="endorsement_note" value="{{ old('endorsement_note', $student->endorsement_note) }}"
                                                       placeholder="Recommendation note..."
                                                       class="border-primary-200 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-[11px] px-2 py-0.5 w-full max-w-[210px]">
                                                @if ($student->hasEndorsement())
                                                    <span class="text-[10px] text-amber-800 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded font-medium inline-block max-w-fit">
                                                        Endorsed for: {{ $student->endorsedCompany?->name }}
                                                    </span>
                                                @endif
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-card>

            {{-- Charts --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <x-card>
                    <x-section-header title="Students by Status" subtitle="Distribution across the OJT lifecycle" />
                    <div class="flex flex-col items-center py-6">
                        <x-donut-chart :percent="$activeRate" center="{{ $activeRate }}%" caption="active rate" />
                    </div>
                    @if ($studentStatusChart->isEmpty())
                        <p class="py-6 text-center text-sm text-gray-500">No students yet.</p>
                    @else
                        <div class="mt-2 flex flex-wrap justify-center gap-3">
                            @foreach ($studentStatusChart as $item)
                                <div class="flex items-center gap-2.5 rounded-lg border border-primary-100 bg-cream-50 px-3.5 py-2">
                                    <x-status-badge :status="$item['status']" />
                                    <span class="font-bold leading-none text-primary-900">{{ $item['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>

                <x-card>
                    <x-section-header title="Applications per Company" subtitle="Where students are applying" />
                    <div class="mt-4">
                        <x-bars-chart :items="$applicationsPerCompany" />
                    </div>
                </x-card>
            </div>

            {{-- Recent applications --}}
            <x-card flush>
                <div class="p-6">
                    <x-section-header title="Recent Applications Across Companies" />
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="px-4 py-3">Student</th>
                                    <th class="px-4 py-3">Position</th>
                                    <th class="px-4 py-3">Company</th>
                                    <th class="px-4 py-3">Applied</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($recentApplications as $application)
                                    <tr class="hover:bg-primary-50 transition">
                                        <td class="px-4 py-3 font-medium text-gray-900">{{ $application->student->user->name }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $application->jobPosting->title }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $application->jobPosting->company->name }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $application->applied_at?->format('M d, Y') }}</td>
                                        <td class="px-4 py-3"><x-status-badge :status="$application->status" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
