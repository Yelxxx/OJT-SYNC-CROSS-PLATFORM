<nav x-data="{ open: false }" class="bg-primary-600 border-b border-primary-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-10 w-auto" variant="light" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-1 sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard', 'student.dashboard', 'coordinator.dashboard', 'hr.dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @auth
                        @if (Auth::user()->isStudent())
                            <x-nav-link :href="route('student.jobs.index')" :active="request()->routeIs('student.jobs.*')">
                                {{ __('Browse Jobs') }}
                            </x-nav-link>
                            <x-nav-link :href="route('student.applications.index')" :active="request()->routeIs('student.applications.*')">
                                {{ __('My Applications') }}
                            </x-nav-link>
                            @if (Auth::user()->studentProfile?->company_id !== null)
                                <x-nav-link :href="route('student.dtr.index')" :active="request()->routeIs('student.dtr.*')">
                                    {{ __('DTR') }}
                                </x-nav-link>
                            @endif
                            <x-nav-link :href="route('student.journals.index')" :active="request()->routeIs('student.journals.*')">
                                {{ __('E-Journals') }}
                            </x-nav-link>
                        @elseif (Auth::user()->isCoordinator())
                            <x-nav-link :href="route('coordinator.dtr.index')" :active="request()->routeIs('coordinator.dtr.*')">
                                {{ __('Time Records') }}
                            </x-nav-link>
                            <x-nav-link :href="route('coordinator.journals.index')" :active="request()->routeIs('coordinator.journals.*')">
                                {{ __('E-Journals') }}
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
                    @endauth
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <!-- Offline sync status chip -->
                    <button type="button" @click="$store.syncStatus.retry()"
                        x-data x-show="$store.syncStatus.pending > 0 || $store.syncStatus.justSynced" x-cloak
                        class="hidden sm:inline-flex items-center gap-1.5 me-3 px-2.5 py-1 rounded-full text-xs font-semibold border transition"
                        :class="$store.syncStatus.pending > 0 ? 'bg-gold-50 border-gold-300 text-gold-700' : 'bg-primary-50 border-primary-300 text-primary-700'">
                        <span class="h-2 w-2 rounded-full"
                            :class="$store.syncStatus.syncing ? 'bg-gold-400 animate-pulse' : ($store.syncStatus.pending > 0 ? 'bg-gold-400' : 'bg-primary-400')"></span>
                        <span x-show="$store.syncStatus.syncing">Syncing…</span>
                        <span x-show="! $store.syncStatus.syncing && $store.syncStatus.pending > 0" x-text="$store.syncStatus.pending + ' waiting to sync'"></span>
                        <span x-show="! $store.syncStatus.syncing && $store.syncStatus.pending === 0">Synced</span>
                    </button>
                @endauth

                <div class="hidden sm:flex items-center me-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gold-400 text-primary-900">
                        {{ \App\Models\User::ROLES[Auth::user()->role] }}
                    </span>
                </div>

                <div class="me-2">
                    <x-notification-bell />
                </div>

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-primary-100 bg-primary-600 hover:text-white focus:outline-none focus:bg-primary-700 transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-primary-100 hover:text-white hover:bg-primary-700 focus:outline-none focus:bg-primary-700 focus:text-white transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->        <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1 bg-primary-700">                    <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard', 'student.dashboard', 'coordinator.dashboard', 'hr.dashboard')">
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
                    @endif
                    <x-responsive-nav-link :href="route('student.journals.index')" :active="request()->routeIs('student.journals.*')">
                        {{ __('E-Journals') }}
                    </x-responsive-nav-link>
                @elseif (Auth::user()->isCoordinator())
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
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-primary-600 bg-primary-700">
            <div class="px-4">
                <div class="flex items-center gap-2">
                    <div class="font-medium text-base text-white">{{ Auth::user()->name }}</div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gold-400 text-primary-900">
                        {{ \App\Models\User::ROLES[Auth::user()->role] }}
                    </span>
                    @auth
                        <button type="button" @click="$store.syncStatus.retry()"
                            x-data x-show="$store.syncStatus.pending > 0 || $store.syncStatus.justSynced" x-cloak
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                            :class="$store.syncStatus.pending > 0 ? 'bg-gold-50 border-gold-300 text-gold-700' : 'bg-primary-50 border-primary-300 text-primary-700'">
                            <span x-show="$store.syncStatus.syncing">Syncing…</span>
                            <span x-show="! $store.syncStatus.syncing && $store.syncStatus.pending > 0" x-text="$store.syncStatus.pending + ' to sync'"></span>
                            <span x-show="! $store.syncStatus.syncing && $store.syncStatus.pending === 0">Synced</span>
                        </button>
                    @endauth
                </div>
                <div class="font-medium text-sm text-primary-200">{{ Auth::user()->email }}</div>
                <div class="mt-2 text-primary-100">
                    <x-notification-bell />
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
