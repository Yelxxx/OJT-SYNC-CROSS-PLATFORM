<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-primary-900 leading-tight">
                {{ __('Direct Messages & Practicum Advisory') }}
            </h2>
            <span class="text-xs text-gray-500 font-medium">
                Official in-app communication channel
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-12 min-h-[620px]">

                    <!-- Left: Contacts List -->
                    <div class="md:col-span-4 lg:col-span-4 border-r border-gray-200 flex flex-col bg-gray-50/50">
                        <div class="p-4 border-b border-gray-200 bg-white">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-500">Authorized Contacts</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Role-verified practicum partners</p>
                        </div>

                        <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
                            @forelse ($contacts as $contact)
                                @php
                                    $isSelected = $activeContact && $activeContact->id === $contact->id;
                                @endphp
                                <a href="{{ route('messages.index', ['with' => $contact->id]) }}"
                                   class="block p-4 transition-colors hover:bg-cream-50 {{ $isSelected ? 'bg-cream-100 border-s-4 border-primary-600' : '' }}">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-1.5">
                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $contact->name }}</p>
                                                @if ($contact->unread_count > 0)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold bg-primary-600 text-white rounded-full">
                                                        {{ $contact->unread_count }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-primary-700 font-medium truncate mt-0.5">
                                                {{ $contact->custom_role_label ?? ucfirst($contact->role) }}
                                            </p>
                                            <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $contact->email }}</p>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="p-6 text-center text-sm text-gray-500">
                                    <svg class="mx-auto h-10 w-10 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                                    </svg>
                                    <p class="font-medium text-gray-700">No active contacts yet</p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        @if (Auth::user()->isStudent())
                                            Once you are accepted by a host company or assigned an academic coordinator, they will appear here.
                                        @elseif (Auth::user()->isHr())
                                            Accepted student interns and their coordinators will appear here.
                                        @else
                                            Your assigned student advisees and their host companies will appear here.
                                        @endif
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Right: Conversation Thread -->
                    <div class="md:col-span-8 lg:col-span-8 flex flex-col bg-white">
                        @if ($activeContact)
                            <!-- Thread Header -->
                            <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-white">
                                <div>
                                    <h3 class="text-base font-bold text-gray-900">{{ $activeContact->name }}</h3>
                                    <p class="text-xs text-gray-500">
                                        {{ $activeContact->custom_role_label ?? ucfirst($activeContact->role) }}
                                        &bull; {{ $activeContact->email }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cream-100 text-primary-800">
                                    Active Thread
                                </span>
                            </div>

                            <!-- Messages Stream -->
                            <div class="flex-1 p-4 md:p-6 overflow-y-auto space-y-4 max-h-[500px] bg-slate-50/40" id="message-container">
                                @forelse ($messages as $message)
                                    @php
                                        $isMine = $message->sender_id === Auth::id();
                                    @endphp
                                    <div class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }}">
                                        <div class="flex items-center gap-1.5 mb-1">
                                            <span class="text-[11px] font-semibold text-gray-600">
                                                {{ $isMine ? 'You' : $message->sender->name }}
                                            </span>
                                            <span class="text-[10px] text-gray-400">
                                                {{ $message->created_at->format('M d, g:i A') }}
                                            </span>
                                        </div>
                                        <div class="max-w-[85%] md:max-w-[70%] rounded-2xl px-4 py-2.5 text-sm shadow-sm {{ $isMine ? 'bg-primary-600 text-white rounded-br-none' : 'bg-white border border-gray-200 text-gray-800 rounded-bl-none' }}">
                                            <p class="whitespace-pre-wrap break-words leading-relaxed">{{ $message->body }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="py-16 text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                        </svg>
                                        <p class="text-sm font-medium text-gray-600">No messages yet with {{ $activeContact->name }}</p>
                                        <p class="text-xs text-gray-400 mt-1">Start the conversation below.</p>
                                    </div>
                                @endforelse
                            </div>

                            <!-- Message Input Form -->
                            <div class="p-4 border-t border-gray-200 bg-white">
                                <form action="{{ route('messages.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="recipient_id" value="{{ $activeContact->id }}">
                                    
                                    <div class="flex flex-col gap-2">
                                        <textarea
                                            name="body"
                                            rows="3"
                                            required
                                            maxlength="2000"
                                            placeholder="Write your message here... (Press Send when ready)"
                                            class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm placeholder:text-gray-400 resize-none"
                                        ></textarea>
                                        
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs text-gray-400">Max 2,000 characters</span>
                                            <button
                                                type="submit"
                                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-800 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                            >
                                                <span>Send Message</span>
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <script>
                                // Scroll message stream to bottom on load
                                document.addEventListener('DOMContentLoaded', function () {
                                    const container = document.getElementById('message-container');
                                    if (container) {
                                        container.scrollTop = container.scrollHeight;
                                    }
                                });
                            </script>
                        @else
                            <!-- No Active Contact Selected -->
                            <div class="flex-1 flex flex-col items-center justify-center p-12 text-center text-gray-500">
                                <svg class="h-16 w-16 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                </svg>
                                <h3 class="text-base font-semibold text-gray-800">Select a Conversation</h3>
                                <p class="text-xs text-gray-400 mt-1 max-w-sm">
                                    Choose an authorized contact from the sidebar to view message history and send messages.
                                </p>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>

