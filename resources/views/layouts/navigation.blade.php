@php
    $notifications = auth()->user()->notifications()->latest()->take(10)->get();
    $unreadCount = $notifications->whereNull('read_at')->count();
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="{{ route('dashboard') }}" class="shrink-0 flex items-center">
                <x-application-logo class="block h-9 w-auto" />
            </a>

            <!-- Kept deliberately minimal — everything else lives behind the menu toggle so the
                 header doesn't get crowded as more roles/modules stack up. -->
            <div class="flex items-center gap-1">
                <x-dropdown align="right" width="w-80">
                    <x-slot name="trigger">
                        <button class="relative p-2 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100" title="Notifications">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            @if ($unreadCount > 0)
                                <span class="absolute top-1 right-1 h-4 min-w-4 px-1 rounded-full bg-indigo-600 text-white text-[10px] font-medium leading-4 text-center">
                                    {{ $unreadCount }}
                                </span>
                            @endif
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
                            <span class="text-sm font-semibold text-gray-700">{{ __('Notifications') }}</span>
                            @if ($notifications->isNotEmpty())
                                <div class="flex items-center gap-3 text-xs">
                                    @if ($unreadCount > 0)
                                        <form method="POST" action="{{ route('notifications.read-all') }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-800">{{ __('Mark all read') }}</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('notifications.destroy-all') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-gray-600">{{ __('Clear all') }}</button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        <div class="max-h-96 overflow-y-auto">
                            @forelse ($notifications as $notification)
                                <div class="flex items-start gap-2 px-4 py-3 border-b border-gray-50 last:border-0 {{ $notification->read_at ? '' : 'bg-indigo-50/40' }}">
                                    <a href="{{ $notification->data['url'] ?? route('dashboard') }}" class="flex-1 text-sm text-gray-700 hover:text-gray-900">
                                        <p>{{ $notification->data['message'] ?? 'Notification' }}</p>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $notification->created_at->diffForHumans() }}</p>
                                    </a>
                                    <form method="POST" action="{{ route('notifications.destroy', $notification->id) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Clear" class="text-gray-300 hover:text-gray-500 p-1">&times;</button>
                                    </form>
                                </div>
                            @empty
                                <p class="px-4 py-6 text-sm text-gray-400 text-center">{{ __("You're all caught up.") }}</p>
                            @endforelse
                        </div>
                    </x-slot>
                </x-dropdown>

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
                        <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>

                <button @click="open = ! open" aria-label="Menu"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu — the only nav surface, at every screen width, so there's no breakpoint where the
         wrong thing shows. Loosely grouped so a long list stays scannable. -->
    <div x-show="open" x-cloak @click.outside="open = false" class="border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>

            @hasrole('admin')
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">{{ __('Users') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('school-settings.edit')" :active="request()->routeIs('school-settings.*')">{{ __('School Settings') }}</x-responsive-nav-link>
            @endhasrole

            @hasanyrole(['admin', 'teacher'])
                <p class="pt-3 pb-1 px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Academics') }}</p>
                <x-responsive-nav-link :href="route('assessments.index')" :active="request()->routeIs('assessments.*')">{{ __('Assessments') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('attendance.create')" :active="request()->routeIs('attendance.*')">{{ __('Attendance') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notices.index')" :active="request()->routeIs('notices.*')">{{ __('Noticeboard') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reports.academics')" :active="request()->routeIs('reports.academics')">{{ __('Academic Trends') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('resources.index')" :active="request()->routeIs('resources.*')">{{ __('Teaching Resources') }}</x-responsive-nav-link>
            @endhasanyrole

            @hasrole('learner')
                <p class="pt-3 pb-1 px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('My Records') }}</p>
                <x-responsive-nav-link :href="route('learner.assessments')" :active="request()->routeIs('learner.assessments')">{{ __('My Assessments') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('learner.attendance')" :active="request()->routeIs('learner.attendance')">{{ __('My Attendance') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('learner.notices')" :active="request()->routeIs('learner.notices')">{{ __('Noticeboard') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('resources.index')" :active="request()->routeIs('resources.*')">{{ __('Teaching Resources') }}</x-responsive-nav-link>
            @endhasrole

            @hasrole('parent')
                <p class="pt-3 pb-1 px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('My Children') }}</p>
                <x-responsive-nav-link :href="route('resources.index')" :active="request()->routeIs('resources.*')">{{ __('Teaching Resources') }}</x-responsive-nav-link>
            @endhasrole

            @hasanyrole(['nurse', 'admin'])
                <p class="pt-3 pb-1 px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Health') }}</p>
                <x-responsive-nav-link :href="route('medications.index')" :active="request()->routeIs('medications.*')">{{ __('eMAR') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('clinic-visits.index')" :active="request()->routeIs('clinic-visits.*')">{{ __('Clinic') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reports.health')" :active="request()->routeIs('reports.health')">{{ __('Health Trends') }}</x-responsive-nav-link>
            @endhasanyrole

            <p class="pt-3 pb-1 px-3 text-xs font-semibold uppercase tracking-wide text-gray-400">{{ __('Operations') }}</p>
            <x-responsive-nav-link :href="route('incidents.index')" :active="request()->routeIs('incidents.*')">{{ __('Issue Reports') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('gate-passes.index')" :active="request()->routeIs('gate-passes.*')">{{ __('Gate Passes') }}</x-responsive-nav-link>
            @hasanyrole(['bursar', 'admin'])
                <x-responsive-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')">{{ __('Invoices') }}</x-responsive-nav-link>
            @endhasanyrole
            @hasanyrole(['hr', 'admin'])
                <x-responsive-nav-link :href="route('payroll-runs.index')" :active="request()->routeIs('payroll-runs.*')">{{ __('Payroll') }}</x-responsive-nav-link>
            @endhasanyrole
            @hasanyrole(['librarian', 'admin'])
                <x-responsive-nav-link :href="route('inventory-items.index')" :active="request()->routeIs('inventory-items.*')">{{ __('Inventory') }}</x-responsive-nav-link>
            @endhasanyrole
            @if (auth()->user()->school?->offersLevel('nursery'))
                @hasanyrole(['teacher', 'nurse', 'admin'])
                    <x-responsive-nav-link :href="route('daily-activity-logs.index')" :active="request()->routeIs('daily-activity-logs.*')">{{ __('Nursery') }}</x-responsive-nav-link>
                @endhasanyrole
            @endif
        </div>

        <div class="border-t border-gray-100 py-3">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>
        </div>
    </div>
</nav>
