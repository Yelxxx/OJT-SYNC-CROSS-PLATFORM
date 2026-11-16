<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-primary-900 leading-tight">
                    {{ __('Early Warning & OJT Pacing Radar') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    Detect at-risk interns, monitor burn-down velocities, and intervene before final grading deadlines.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                    🔴 {{ $counts['at_risk'] }} At Risk
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                    🟡 {{ $counts['slow'] }} Lagging
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                    🟢 {{ $counts['on_track'] }} On Track
                </span>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm flex items-center justify-between">
                    <span>{{ session('status') }}</span>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
                </div>
            @endif

            <!-- Quick Filter Tabs -->
            <div class="flex items-center gap-2 border-b border-gray-200 pb-3 overflow-x-auto">
                <a href="{{ route('coordinator.pacing.index', ['filter' => 'all']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentFilter === 'all' ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-100' }}">
                    All Advisees ({{ $counts['all'] }})
                </a>
                <a href="{{ route('coordinator.pacing.index', ['filter' => 'at_risk']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentFilter === 'at_risk' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                    🔴 At Risk ({{ $counts['at_risk'] }})
                </a>
                <a href="{{ route('coordinator.pacing.index', ['filter' => 'slow']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentFilter === 'slow' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                    🟡 Pacing Slow ({{ $counts['slow'] }})
                </a>
                <a href="{{ route('coordinator.pacing.index', ['filter' => 'on_track']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentFilter === 'on_track' ? 'bg-emerald-600 text-white' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                    🟢 On Track ({{ $counts['on_track'] }})
                </a>
                <a href="{{ route('coordinator.pacing.index', ['filter' => 'completed']) }}"
                   class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition {{ $currentFilter === 'completed' ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                    🏁 Completed ({{ $counts['completed'] }})
                </a>
            </div>

            <!-- Student Pacing Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @forelse ($students as $student)
                    @php
                        $p = $student->pacing;
                    @endphp
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col justify-between hover:border-primary-300 transition"
                         x-data="{ showNudgeModal: false, showTimelineModal: false }">
                        
                        <div class="p-5">
                            <!-- Card Header: Student & Risk Badge -->
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="font-bold text-gray-900 text-base truncate">{{ $student->user->name }}</h3>
                                    <p class="text-xs text-gray-500">{{ $student->student_no }} &bull; {{ $student->program }}</p>
                                    <p class="text-xs text-primary-700 font-medium truncate mt-0.5">
                                        {{ $student->company?->name ?? 'No Company Placed' }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold border {{ $p['badge_class'] }}">
                                    {{ $p['status_label'] }}
                                </span>
                            </div>

                            <!-- Progress Bar & Metrics -->
                            <div class="mt-4 pt-3 border-t border-gray-100">
                                <div class="flex items-center justify-between text-xs font-semibold">
                                    <span class="text-gray-600">Hours Progress</span>
                                    <span class="text-primary-900">{{ number_format($p['completed_hours'], 1) }} / {{ $student->required_hours }} hrs ({{ $p['progress_percent'] }}%)</span>
                                </div>
                                <div class="w-full bg-gray-100 rounded-full h-2 mt-1.5">
                                    <div class="bg-primary-600 h-2 rounded-full transition-all" style="width: {{ min(100, $p['progress_percent']) }}%"></div>
                                </div>
                            </div>

                            <!-- Pacing Key Stats -->
                            <div class="grid grid-cols-2 gap-2 mt-4 text-xs bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                                <div>
                                    <span class="text-gray-400 block text-[11px]">Required Pace:</span>
                                    <span class="font-bold text-gray-900">
                                        {{ $p['required_pace_weekly'] > 0 ? $p['required_pace_weekly'].' hrs/wk' : 'Target Met' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[11px]">Time Left:</span>
                                    <span class="font-bold text-gray-900">{{ $p['days_remaining'] }} days ({{ $p['weeks_remaining'] }} wks)</span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[11px]">Last Clock-in:</span>
                                    <span class="font-bold {{ ($p['days_since_last_clockin'] ?? 0) >= 7 ? 'text-rose-600' : 'text-gray-900' }}">
                                        {{ $p['days_since_last_clockin'] !== null ? $p['days_since_last_clockin'].' days ago' : 'Never' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-400 block text-[11px]">Target End:</span>
                                    <span class="font-bold text-gray-900">
                                        {{ $p['target_end_date'] ? \Illuminate\Support\Carbon::parse($p['target_end_date'])->format('M d, Y') : 'Not Set' }}
                                    </span>
                                </div>
                            </div>

                            <!-- Early Warning Indicators / Reasons -->
                            @if (! empty($p['risk_reasons']))
                                <div class="mt-3 p-2 bg-rose-50 border border-rose-200 rounded-lg space-y-1">
                                    @foreach ($p['risk_reasons'] as $reason)
                                        <div class="flex items-start gap-1.5 text-[11px] text-rose-800 leading-tight">
                                            <span class="font-bold shrink-0">⚠️</span>
                                            <span>{{ $reason }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if ($student->last_nudge_at)
                                <p class="text-[10px] text-gray-400 mt-2 italic text-right">
                                    Last coordinator notice sent {{ $student->last_nudge_at->diffForHumans() }}
                                </p>
                            @endif
                        </div>

                        <!-- Card Footer: Actions -->
                        <div class="p-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-2 rounded-b-xl">
                            <button type="button" @click="showTimelineModal = true"
                                    class="text-xs text-gray-600 hover:text-gray-900 font-medium px-2 py-1 rounded hover:bg-gray-200 transition">
                                📅 Edit Timeline
                            </button>

                            @if ($p['status'] !== 'completed')
                                <button type="button" @click="showNudgeModal = true"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                                    <span>⚠️ Intervene / Nudge</span>
                                </button>
                            @else
                                <span class="text-xs text-emerald-700 font-semibold px-2">✓ Certified</span>
                            @endif
                        </div>

                        <!-- Modal: Send Intervention Nudge -->
                        <div x-show="showNudgeModal"
                             class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 flex items-center justify-center p-4"
                             style="display: none;">
                            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left" @click.outside="showNudgeModal = false">
                                <h3 class="font-bold text-base text-gray-900">Intervention Notice: {{ $student->user->name }}</h3>
                                <p class="text-xs text-gray-500 mt-1">
                                    Send an urgent pacing inquiry to the intern and their company supervisor.
                                </p>

                                <form method="POST" action="{{ route('coordinator.pacing.students.nudge', $student) }}" class="mt-4 space-y-4">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Notice Message</label>
                                        <textarea name="message" rows="4" required
                                                  class="w-full text-xs rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">Good day {{ $student->user->name }}, our practicum monitoring shows you are currently at {{ number_format($p['completed_hours'], 1) }} / {{ $student->required_hours }} hours (needs {{ $p['required_pace_weekly'] }} hrs/wk). Please confirm if you are encountering scheduling conflicts or shift delays at your host company.</textarea>
                                    </div>

                                    @if ($student->company_id)
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" id="notify_hr_{{ $student->id }}" name="notify_hr" value="1" checked
                                                   class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                            <label for="notify_hr_{{ $student->id }}" class="text-xs text-gray-700 font-medium">
                                                Also send inquiry to {{ $student->company->name }} HR Mentor
                                            </label>
                                        </div>
                                    @endif

                                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                                        <button type="button" @click="showNudgeModal = false"
                                                class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700 font-medium">Cancel</button>
                                        <button type="submit"
                                                class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                            Send Notice &rarr;
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Modal: Edit Timeline -->
                        <div x-show="showTimelineModal"
                             class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/50 flex items-center justify-center p-4"
                             style="display: none;">
                            <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 text-left" @click.outside="showTimelineModal = false">
                                <h3 class="font-bold text-base text-gray-900">Adjust Timeline: {{ $student->user->name }}</h3>
                                <p class="text-xs text-gray-500 mt-1">Configure target deadline and required hours for pacing calculations.</p>

                                <form method="POST" action="{{ route('coordinator.pacing.students.timeline', $student) }}" class="mt-4 space-y-3">
                                    @csrf
                                    @method('PATCH')
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Required Hours Target</label>
                                        <input type="number" name="required_hours" value="{{ $student->required_hours }}" required min="50" max="1000"
                                               class="w-full text-xs rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Deployment Date</label>
                                        <input type="date" name="deployment_date" value="{{ $student->deployment_date?->format('Y-m-d') }}"
                                               class="w-full text-xs rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1">Target End / Semester Deadline</label>
                                        <input type="date" name="target_end_date" value="{{ $student->target_end_date?->format('Y-m-d') }}"
                                               class="w-full text-xs rounded-lg border-gray-300 focus:border-primary-500 focus:ring-primary-500">
                                    </div>
                                    <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                                        <button type="button" @click="showTimelineModal = false"
                                                class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700 font-medium">Cancel</button>
                                        <button type="submit"
                                                class="px-4 py-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                            Save Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                @empty
                    <div class="col-span-full bg-white p-12 text-center rounded-xl border border-gray-200">
                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h4 class="text-sm font-bold text-gray-800">No students match this filter</h4>
                        <p class="text-xs text-gray-400 mt-1">Switch tabs above to view other advisees.</p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
