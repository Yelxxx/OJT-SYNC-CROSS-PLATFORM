@php
    $notifications = auth()->check() ? auth()->user()->unreadNotifications : collect();
@endphp

<div class="relative" x-data="{ open: false }" @click.outside="open = false">
    <button @click="open = ! open"
            class="relative inline-flex items-center justify-center p-2 rounded-md text-primary-100 hover:text-white hover:bg-primary-700 focus:outline-none transition"
            :aria-expanded="open"
            aria-label="Notifications">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        @if ($notifications->isNotEmpty())
            <span class="absolute top-1 end-1 flex h-4 min-w-4 px-1 items-center justify-center rounded-full bg-gold-400 text-[10px] font-bold text-primary-900">
                {{ $notifications->count() > 9 ? '9+' : $notifications->count() }}
            </span>
        @endif
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         style="display: none;"
         class="absolute end-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-50"
         @click="open = false">
        <div class="py-1">
            <p class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400">Notifications</p>

            @if ($notifications->isEmpty())
                <p class="px-4 py-3 text-sm text-gray-400">You're all caught up! 🎉</p>
            @else
                @foreach ($notifications->take(8) as $notification)
                    <a href="{{ route('notifications.read', $notification) }}"
                       class="block px-4 py-3 text-sm hover:bg-cream-50 transition">
                        @if ($notification->type === \App\Notifications\ApplicationStatusChanged::class)
                            @php $d = $notification->data; @endphp
                            <p class="font-medium text-gray-900">
                                {{ $d['company_name'] }} — {{ \App\Models\Application::STATUSES[$d['status']] ?? $d['status'] }}
                            </p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Your application for {{ $d['job_title'] }}
                                @if ($d['status'] === \App\Models\Application::STATUS_INTERVIEW && $d['interview_at'])
                                    · interview {{ \Illuminate\Support\Carbon::parse($d['interview_at'])->format('M d, g:i A') }}
                                @elseif ($d['status'] === \App\Models\Application::STATUS_REJECTED && $d['rejection_reason'])
                                    · {{ \Illuminate\Support\Str::limit($d['rejection_reason'], 60) }}
                                @endif
                            </p>
                        @elseif ($notification->type === \App\Notifications\NewApplicationReceived::class)
                            @php $d = $notification->data; @endphp
                            <p class="font-medium text-gray-900">New application received</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $d['student_name'] }} applied for {{ $d['job_title'] }}
                            </p>
                        @else
                            <p class="font-medium text-gray-900">{{ $notification->type }}</p>
                        @endif
                        <p class="text-[10px] text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </a>
                @endforeach

                <form method="POST" action="{{ route('notifications.markAllRead') }}" class="border-t border-gray-100">
                    @csrf
                    <button type="submit" class="w-full text-center px-4 py-2 text-xs font-medium text-primary-600 hover:bg-cream-50 transition">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
