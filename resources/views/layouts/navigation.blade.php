<nav x-data="{ open: false }" style="background-color: #2E7D32;" class="border-b border-[#256A29] shadow-md sticky top-0 z-40">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            
            <!-- Left: Brand Logo & Links -->
            <div class="flex items-center gap-5 min-w-0 flex-1">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 group transition">
                        <x-application-logo class="block h-9 w-auto" variant="color" />
                        <span class="text-sm font-extrabold tracking-tight text-white leading-none">OJT SYNC</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:flex items-center gap-1.5 overflow-x-auto py-2 no-scrollbar">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard', 'student.dashboard', 'coordinator.dashboard', 'hr.dashboard')">
                        <svg class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                        <span>{{ __('Dashboard') }}</span>
                    </x-nav-link>

                    @auth
                        @if (Auth::user()->isStudent())
                            <x-nav-link :href="route('student.jobs.index')" :active="request()->routeIs('student.jobs.*')">
                                {{ __('Jobs') }}
                            </x-nav-link>
                            <x-nav-link :href="route('student.applications.index')" :active="request()->routeIs('student.applications.*')">
                                {{ __('Applications') }}
                            </x-nav-link>
                            @if (Auth::user()->studentProfile?->company_id !== null)
                                <x-nav-link :href="route('student.dtr.index')" :active="request()->routeIs('student.dtr.*')">
                                    {{ __('DTR') }}
                                </x-nav-link>
                                <x-nav-link :href="route('student.checklist.index')" :active="request()->routeIs('student.checklist.*')">
                                    {{ __('Clearance') }}
                                </x-nav-link>
                                <x-nav-link :href="route('student.leaves.index')" :active="request()->routeIs('student.leaves.*')">
                                    {{ __('Leaves') }}
                                </x-nav-link>
                                <x-nav-link :href="route('student.portfolio.index')" :active="request()->routeIs('student.portfolio.*')">
                                    {{ __('Skills') }}
                                </x-nav-link>
                            @endif
                            <x-nav-link :href="route('student.journals.index')" :active="request()->routeIs('student.journals.*')">
                                {{ __('Journals') }}
                            </x-nav-link>
                        @elseif (Auth::user()->isCoordinator())
                            <x-nav-link :href="route('coordinator.clearance.index')" :active="request()->routeIs('coordinator.clearance.*')">
                                {{ __('Clearance & MOA') }}
                            </x-nav-link>
                            <x-nav-link :href="route('coordinator.pacing.index')" :active="request()->routeIs('coordinator.pacing.*')">
                                {{ __('Pacing Radar') }}
                            </x-nav-link>
                            <x-nav-link :href="route('coordinator.dtr.index')" :active="request()->routeIs('coordinator.dtr.*')">
                                {{ __('Time Records') }}
                            </x-nav-link>
                            <x-nav-link :href="route('coordinator.journals.index')" :active="request()->routeIs('coordinator.journals.*')">
                                {{ __('Journals') }}
                            </x-nav-link>
                            <x-nav-link :href="route('coordinator.evaluations.index')" :active="request()->routeIs('coordinator.evaluations.*')">
                                {{ __('Evaluations') }}
                            </x-nav-link>
                        @elseif (Auth::user()->isHr())
                            <x-nav-link :href="route('hr.jobs.index')" :active="request()->routeIs('hr.jobs.*')">
                                {{ __('Job Postings') }}
                            </x-nav-link>
                            <x-nav-link :href="route('hr.applications.index')" :active="request()->routeIs('hr.applications.*')">
                                {{ __('Applications') }}
                            </x-nav-link>
                            <x-nav-link :href="route('hr.interns.index')" :active="request()->routeIs('hr.interns.*')">
                                {{ __('My Interns') }}
                            </x-nav-link>
                            <x-nav-link :href="route('hr.leaves.index')" :active="request()->routeIs('hr.leaves.*')">
                                {{ __('Leaves') }}
                            </x-nav-link>
                            <x-nav-link :href="route('hr.dtr.index')" :active="request()->routeIs('hr.dtr.*')">
                                {{ __('Time Records') }}
                            </x-nav-link>
                            <x-nav-link :href="route('hr.evaluations.index')" :active="request()->routeIs('hr.evaluations.*')">
                                {{ __('Evaluations') }}
                            </x-nav-link>
                            <x-nav-link :href="route('hr.company.edit')" :active="request()->routeIs('hr.company.*')">
                                {{ __('Company') }}
                            </x-nav-link>
                        @elseif (Auth::user()->isAdmin())
                            <x-nav-link :href="route('admin.companies.index')" :active="request()->routeIs('admin.companies.*')">
                                {{ __('Company Applications') }}
                            </x-nav-link>
                        @endif

                        @if (! Auth::user()->isAdmin())
                            <x-nav-link :href="route('messages.index')" :active="request()->routeIs('messages.*')" class="relative">
                                <span>{{ __('Messages') }}</span>
                                @php
                                    $unreadMessagesCount = \App\Models\Message::where('recipient_id', Auth::id())->whereNull('read_at')->count();
                                @endphp
                                @if ($unreadMessagesCount > 0)
                                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold leading-none text-white bg-rose-500 rounded-full shadow-sm">
                                        {{ $unreadMessagesCount > 9 ? '9+' : $unreadMessagesCount }}
                                    </span>
                                @endif
                            </x-nav-link>
                        @endif
                    @endauth
                </div>
            </div>

            <!-- Right Controls: Role Badge, Notification Bell, User Menu -->
            <div class="hidden sm:flex sm:items-center sm:gap-3 shrink-0">
                <!-- Role Tag (CvSU Gold) -->
                <div class="flex items-center">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-extrabold tracking-wide uppercase bg-gold-400 text-primary-950 shadow-sm">
                        {{ \App\Models\User::ROLES[Auth::user()->role] }}
                    </span>
                </div>

                <!-- Notification Bell -->
                <div class="text-emerald-100 hover:text-white transition">
                    <x-notification-bell />
                </div>

                <!-- User Profile Dropdown -->
                <div class="border-s border-emerald-600/60 ps-3">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-emerald-700/60 hover:bg-emerald-700 border border-emerald-500/40 focus:outline-none transition">
                                <div class="w-6 h-6 rounded-full bg-gold-400 text-primary-950 font-bold flex items-center justify-center text-[11px]">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <span class="max-w-[120px] truncate text-left">{{ Auth::user()->name }}</span>
                                <svg class="fill-current h-3.5 w-3.5 text-emerald-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-xs font-bold text-gray-900 truncate">{{ Auth::user()->name }}</p>
                                <p class="text-[11px] text-gray-500 truncate">{{ Auth::user()->email }}</p>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Account Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>

            <!-- Hamburger Button for Mobile -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-emerald-100 hover:text-white hover:bg-emerald-700/60 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

        </div>
    </div>

    <!-- Responsive Mobile Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-emerald-700" style="background-color: #256A29;">
        <div class="pt-2 pb-3 px-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard', 'student.dashboard', 'coordinator.dashboard', 'hr.dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @auth
                @if (Auth::user()->isStudent())
                    <x-responsive-nav-link :href="route('student.jobs.index')" :active="request()->routeIs('student.jobs.*')">
                        {{ __('Browse Jobs') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('student.applications.index')" :active="request()->routeIs('student.applications.*')">
                        {{ __('My Applications') }}
                    </x-responsive-nav-link>
                    @if (Auth::user()->studentProfile?->company_id !== null)
                        <x-responsive-nav-link :href="route('student.dtr.index')" :active="request()->routeIs('student.dtr.*')">
                            {{ __('DTR') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('student.checklist.index')" :active="request()->routeIs('student.checklist.*')">
                            {{ __('Clearance & Requirements') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('student.leaves.index')" :active="request()->routeIs('student.leaves.*')">
                            {{ __('Leaves') }}
                        </x-responsive-nav-link>
                        <x-responsive-nav-link :href="route('student.portfolio.index')" :active="request()->routeIs('student.portfolio.*')">
                            {{ __('Competencies & Skills') }}
                        </x-responsive-nav-link>
                    @endif
                    <x-responsive-nav-link :href="route('student.journals.index')" :active="request()->routeIs('student.journals.*')">
                        {{ __('E-Journals') }}
                    </x-responsive-nav-link>
                @elseif (Auth::user()->isCoordinator())
                    <x-responsive-nav-link :href="route('coordinator.clearance.index')" :active="request()->routeIs('coordinator.clearance.*')">
                        {{ __('Clearance & MOA') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('coordinator.pacing.index')" :active="request()->routeIs('coordinator.pacing.*')">
                        {{ __('Pacing Radar') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('coordinator.dtr.index')" :active="request()->routeIs('coordinator.dtr.*')">
                        {{ __('Time Records') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('coordinator.journals.index')" :active="request()->routeIs('coordinator.journals.*')">
                        {{ __('E-Journals') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('coordinator.evaluations.index')" :active="request()->routeIs('coordinator.evaluations.*')">
                        {{ __('Evaluations') }}
                    </x-responsive-nav-link>
                @elseif (Auth::user()->isHr())
                    <x-responsive-nav-link :href="route('hr.jobs.index')" :active="request()->routeIs('hr.jobs.*')">
                        {{ __('Job Postings') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hr.applications.index')" :active="request()->routeIs('hr.applications.*')">
                        {{ __('Applications') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hr.interns.index')" :active="request()->routeIs('hr.interns.*')">
                        {{ __('My Interns') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hr.leaves.index')" :active="request()->routeIs('hr.leaves.*')">
                        {{ __('Leaves') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hr.dtr.index')" :active="request()->routeIs('hr.dtr.*')">
                        {{ __('Time Records') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hr.evaluations.index')" :active="request()->routeIs('hr.evaluations.*')">
                        {{ __('Evaluations') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('hr.company.edit')" :active="request()->routeIs('hr.company.*')">
                        {{ __('Company') }}
                    </x-responsive-nav-link>
                @elseif (Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('admin.companies.index')" :active="request()->routeIs('admin.companies.*')">
                        {{ __('Company Applications') }}
                    </x-responsive-nav-link>
                @endif

                @if (! Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('messages.index')" :active="request()->routeIs('messages.*')" class="flex items-center justify-between">
                        <span>{{ __('Messages') }}</span>
                        @php
                            $unreadMessagesCountResponsive = \App\Models\Message::where('recipient_id', Auth::id())->whereNull('read_at')->count();
                        @endphp
                        @if ($unreadMessagesCountResponsive > 0)
                            <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-rose-500 rounded-full">
                                {{ $unreadMessagesCountResponsive }}
                            </span>
                        @endif
                    </x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-3 pb-3 border-t border-emerald-700 px-4">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-bold text-sm text-white">{{ Auth::user()->name }}</div>
                    <div class="font-normal text-xs text-emerald-200">{{ Auth::user()->email }}</div>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gold-400 text-primary-950">
                    {{ \App\Models\User::ROLES[Auth::user()->role] }}
                </span>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile Settings') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
