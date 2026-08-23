<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-5 xl:-my-px xl:ms-8 xl:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    @hasrole('admin')
                        <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                            {{ __('Users') }}
                        </x-nav-link>
                    @endhasrole

                    @hasanyrole(['admin', 'teacher'])
                        <x-nav-dropdown label="Academics" :active="request()->routeIs(['assessments.*', 'attendance.*', 'notices.*'])">
                            <x-dropdown-link :href="route('assessments.index')">{{ __('Assessments') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('attendance.create')">{{ __('Attendance') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('notices.index')">{{ __('Noticeboard') }}</x-dropdown-link>
                        </x-nav-dropdown>
                    @endhasanyrole

                    <x-nav-link :href="route('incidents.index')" :active="request()->routeIs('incidents.*')">
                        {{ __('Issue Reports') }}
                    </x-nav-link>

                    @hasanyrole(['bursar', 'admin'])
                        <x-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')">
                            {{ __('Invoices') }}
                        </x-nav-link>
                    @endhasanyrole

                    @hasrole('learner')
                        <x-nav-link :href="route('learner.assessments')" :active="request()->routeIs('learner.assessments')">
                            {{ __('My Assessments') }}
                        </x-nav-link>
                        <x-nav-link :href="route('learner.attendance')" :active="request()->routeIs('learner.attendance')">
                            {{ __('My Attendance') }}
                        </x-nav-link>
                        <x-nav-link :href="route('learner.notices')" :active="request()->routeIs('learner.notices')">
                            {{ __('Noticeboard') }}
                        </x-nav-link>
                    @endhasrole

                    @hasanyrole(['nurse', 'admin'])
                        <x-nav-dropdown label="Health" :active="request()->routeIs(['medications.*', 'clinic-visits.*'])">
                            <x-dropdown-link :href="route('medications.index')">{{ __('eMAR') }}</x-dropdown-link>
                            <x-dropdown-link :href="route('clinic-visits.index')">{{ __('Clinic') }}</x-dropdown-link>
                        </x-nav-dropdown>
                    @endhasanyrole

                    @hasanyrole(['hr', 'admin'])
                        <x-nav-link :href="route('payroll-runs.index')" :active="request()->routeIs('payroll-runs.*')">
                            {{ __('Payroll') }}
                        </x-nav-link>
                    @endhasanyrole

                    @hasanyrole(['librarian', 'admin'])
                        <x-nav-link :href="route('inventory-items.index')" :active="request()->routeIs('inventory-items.*')">
                            {{ __('Inventory') }}
                        </x-nav-link>
                    @endhasanyrole

                    @if (auth()->user()->school?->offersLevel('nursery'))
                        @hasanyrole(['teacher', 'nurse', 'admin'])
                            <x-nav-link :href="route('daily-activity-logs.index')" :active="request()->routeIs('daily-activity-logs.*')">
                                {{ __('Nursery') }}
                            </x-nav-link>
                        @endhasanyrole
                    @endif

                    @hasrole('admin')
                        <x-nav-link :href="route('school-settings.edit')" :active="request()->routeIs('school-settings.*')">
                            {{ __('School Settings') }}
                        </x-nav-link>
                    @endhasrole
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden lg:flex lg:items-center lg:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
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
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @hasanyrole(['admin', 'teacher'])
                <x-responsive-nav-link :href="route('assessments.index')">{{ __('Assessments') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('attendance.create')">{{ __('Attendance') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notices.index')">{{ __('Noticeboard') }}</x-responsive-nav-link>
            @endhasanyrole

            <x-responsive-nav-link :href="route('incidents.index')">{{ __('Issue Reports') }}</x-responsive-nav-link>

            @hasanyrole(['bursar', 'admin'])
                <x-responsive-nav-link :href="route('invoices.index')">{{ __('Invoices') }}</x-responsive-nav-link>
            @endhasanyrole

            @hasrole('learner')
                <x-responsive-nav-link :href="route('learner.assessments')">{{ __('My Assessments') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('learner.attendance')">{{ __('My Attendance') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('learner.notices')">{{ __('Noticeboard') }}</x-responsive-nav-link>
            @endhasrole

            @hasanyrole(['nurse', 'admin'])
                <x-responsive-nav-link :href="route('medications.index')">{{ __('eMAR') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('clinic-visits.index')">{{ __('Clinic') }}</x-responsive-nav-link>
            @endhasanyrole

            @hasanyrole(['hr', 'admin'])
                <x-responsive-nav-link :href="route('payroll-runs.index')">{{ __('Payroll') }}</x-responsive-nav-link>
            @endhasanyrole

            @hasanyrole(['librarian', 'admin'])
                <x-responsive-nav-link :href="route('inventory-items.index')">{{ __('Inventory') }}</x-responsive-nav-link>
            @endhasanyrole

            @if (auth()->user()->school?->offersLevel('nursery'))
                @hasanyrole(['teacher', 'nurse', 'admin'])
                    <x-responsive-nav-link :href="route('daily-activity-logs.index')">{{ __('Nursery') }}</x-responsive-nav-link>
                @endhasanyrole
            @endif

            @hasrole('admin')
                <x-responsive-nav-link :href="route('school-settings.edit')">{{ __('School Settings') }}</x-responsive-nav-link>
            @endhasrole
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
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
