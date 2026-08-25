@php
    $notifications = auth()->user()->notifications()->latest()->take(10)->get();
    $unreadCount = $notifications->whereNull('read_at')->count();
@endphp

{{--
    The off-canvas menu below is a SIBLING of <nav>, not nested inside it. `backdrop-blur-md` on
    the sticky bar (like `transform`/`filter`) makes that element a containing block for any
    `position: fixed` descendant — so a fixed-position drawer nested inside it resolves against
    the 64px-tall bar instead of the viewport and never visibly opens. Keeping x-data on this
    outer div (instead of on <nav>) keeps both siblings in the same Alpine scope without that trap.
--}}
<div x-data="{ open: false }">
    <nav class="sticky top-0 z-40 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-100/80 dark:border-gray-800/80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="{{ route('dashboard') }}" class="shrink-0 flex items-center">
                    <x-application-logo class="block h-9 w-auto" />
                </a>

                {{-- One menu trigger, not two — the hamburger drawer covers navigation, profile,
                     and logout, so there's no separate avatar dropdown competing with it. --}}
                <div class="flex items-center gap-1">
                    <x-dropdown align="right" width="w-80">
                        <x-slot name="trigger">
                            <button class="relative p-2.5 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-900/5 transition-colors duration-150" title="Notifications">
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

                    <button @click="open = true" aria-label="Open menu"
                        class="inline-flex items-center justify-center p-2 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-900/5 focus:outline-none transition-colors duration-150">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Off-canvas menu — a real drawer with a blurred backdrop instead of an in-flow block that
         used to shove all page content down when opened. Body scroll is locked while open so the
         page behind the backdrop doesn't scroll along with it. -->
    <div x-show="open" x-cloak @keydown.escape.window="open = false" x-effect="document.body.classList.toggle('overflow-hidden', open)" class="relative z-50">
        <div x-show="open" x-transition:enter="transition-opacity ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click="open = false" class="fixed inset-0 bg-gray-900/40 backdrop-blur-sm"></div>

        <div x-show="open" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
            @click.outside="open = false"
            class="fixed inset-y-0 right-0 w-full sm:w-96 bg-white/90 dark:bg-gray-900/95 backdrop-blur-xl shadow-2xl ring-1 ring-gray-950/5 dark:ring-white/10 flex flex-col">

            <div class="flex items-center gap-3 px-4 py-5 border-b border-gray-100 dark:border-gray-800 bg-gradient-to-br from-indigo-50/80 to-white dark:from-indigo-950/40 dark:to-gray-900">
                <div class="h-11 w-11 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold shadow-sm shadow-indigo-600/30">
                    {{ collect(explode(' ', Auth::user()->name))->map(fn ($n) => $n[0] ?? '')->take(2)->implode('') }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-800 truncate">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-gray-500 truncate">{{ Auth::user()->email ?? Auth::user()->phone }}</div>
                </div>
                <button @click="open = false" aria-label="Close menu" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-900/5 shrink-0">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-3 py-3 space-y-0.5">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('calendar.index')" :active="request()->routeIs('calendar.*')" icon="calendar">
                {{ __('Academic Calendar') }}
            </x-responsive-nav-link>

            {{-- HR manages staff, not students — deliberately excluded here. --}}
            @hasanyrole(['admin', 'teacher', 'nurse', 'bursar', 'librarian'])
                <x-responsive-nav-link :href="route('students.index')" :active="request()->routeIs('students.*')" icon="users">{{ __('Students') }}</x-responsive-nav-link>
            @endhasanyrole
            @hasrole('admin')
                <x-responsive-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')" icon="identification">{{ __('Users') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('school-settings.edit')" :active="request()->routeIs('school-settings.*')" icon="cog">{{ __('School Settings') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('streams.index')" :active="request()->routeIs('streams.*')" icon="identification">{{ __('Streams') }}</x-responsive-nav-link>
            @endhasrole

            @hasanyrole(['admin', 'teacher'])
                <p class="pt-4 pb-1 px-3 text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ __('Academics') }}</p>
                <x-responsive-nav-link :href="route('assessments.index')" :active="request()->routeIs('assessments.*')" icon="clipboard-check">{{ __('Assessments') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('attendance.create')" :active="request()->routeIs('attendance.*')" icon="calendar">{{ __('Attendance') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('notices.index')" :active="request()->routeIs('notices.*')" icon="megaphone">{{ __('Noticeboard') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reports.academics')" :active="request()->routeIs('reports.academics')" icon="chart-bar">{{ __('Academic Trends') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('resources.index')" :active="request()->routeIs('resources.*')" icon="book-open">{{ __('Teaching Resources') }}</x-responsive-nav-link>
            @endhasanyrole

            @hasrole('learner')
                <p class="pt-4 pb-1 px-3 text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ __('My Records') }}</p>
                <x-responsive-nav-link :href="route('learner.assessments')" :active="request()->routeIs('learner.assessments')" icon="clipboard-check">{{ __('My Assessments') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('learner.attendance')" :active="request()->routeIs('learner.attendance')" icon="calendar">{{ __('My Attendance') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('learner.notices')" :active="request()->routeIs('learner.notices')" icon="megaphone">{{ __('Noticeboard') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('resources.index')" :active="request()->routeIs('resources.*')" icon="book-open">{{ __('Teaching Resources') }}</x-responsive-nav-link>
            @endhasrole

            @hasrole('parent')
                <p class="pt-4 pb-1 px-3 text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ __('My Children') }}</p>
                <x-responsive-nav-link :href="route('resources.index')" :active="request()->routeIs('resources.*')" icon="book-open">{{ __('Teaching Resources') }}</x-responsive-nav-link>
            @endhasrole

            @hasanyrole(['nurse', 'admin'])
                <p class="pt-4 pb-1 px-3 text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ __('Health') }}</p>
                <x-responsive-nav-link :href="route('medications.index')" :active="request()->routeIs('medications.*')" icon="heart-pulse">{{ __('eMAR') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('clinic-visits.index')" :active="request()->routeIs('clinic-visits.*')" icon="plus-circle">{{ __('Clinic') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('reports.health')" :active="request()->routeIs('reports.health')" icon="chart-bar">{{ __('Health Trends') }}</x-responsive-nav-link>
            @endhasanyrole

            <p class="pt-4 pb-1 px-3 text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ __('Operations') }}</p>
            <x-responsive-nav-link :href="route('periods.index')" :active="request()->routeIs('periods.*')" icon="clock">{{ __('Timetable') }}</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('incidents.index')" :active="request()->routeIs('incidents.*')" icon="exclamation-triangle">{{ __('Issue Reports') }}</x-responsive-nav-link>
            {{-- HR/bursar/librarian have no operational reason to manage a student leaving campus. --}}
            @hasanyrole(['admin', 'teacher', 'nurse', 'parent', 'learner'])
                <x-responsive-nav-link :href="route('gate-passes.index')" :active="request()->routeIs('gate-passes.*')" icon="door">{{ __('Gate Passes') }}</x-responsive-nav-link>
            @endhasanyrole
            @hasanyrole(['bursar', 'admin'])
                <x-responsive-nav-link :href="route('invoices.index')" :active="request()->routeIs('invoices.*')" icon="banknotes">{{ __('Invoices') }}</x-responsive-nav-link>
            @endhasanyrole
            @hasanyrole(['hr', 'admin'])
                <x-responsive-nav-link :href="route('payroll-runs.index')" :active="request()->routeIs('payroll-runs.*')" icon="credit-card">{{ __('Payroll') }}</x-responsive-nav-link>
            @endhasanyrole
            @hasanyrole(['librarian', 'admin'])
                <x-responsive-nav-link :href="route('inventory-items.index')" :active="request()->routeIs('inventory-items.*')" icon="archive-box">{{ __('Inventory') }}</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('book-loans.index')" :active="request()->routeIs('book-loans.*')" icon="book-open">{{ __('Library Loans') }}</x-responsive-nav-link>
            @endhasanyrole
            @hasanyrole(['parent', 'learner'])
                <x-responsive-nav-link :href="route('book-loans.index')" :active="request()->routeIs('book-loans.*')" icon="book-open">{{ __('Library Loans') }}</x-responsive-nav-link>
            @endhasanyrole
            @if (auth()->user()->school?->offersLevel('nursery'))
                @hasanyrole(['teacher', 'nurse', 'admin'])
                    <x-responsive-nav-link :href="route('daily-activity-logs.index')" :active="request()->routeIs('daily-activity-logs.*')" icon="sparkles">{{ __('Nursery') }}</x-responsive-nav-link>
                @endhasanyrole
            @endif
            </div>

            <div class="border-t border-gray-100 dark:border-gray-800 px-3 py-3 space-y-0.5">
                <button type="button" @click="$store.theme.toggle()"
                    class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white hover:bg-gray-900/5 dark:hover:bg-white/5 transition-colors duration-150">
                    <x-nav-icon x-show="!$store.theme.dark" name="moon" class="text-gray-400" />
                    <x-nav-icon x-show="$store.theme.dark" name="sun" class="text-gray-400" x-cloak />
                    <span x-text="$store.theme.dark ? '{{ __('Light mode') }}' : '{{ __('Dark mode') }}'"></span>
                </button>
                <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.edit')" icon="user">{{ __('Profile') }}</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex items-center gap-3 w-full px-3 py-2.5 rounded-xl text-sm font-medium text-gray-600 hover:text-red-600 hover:bg-red-50 transition-colors duration-150">
                        <x-nav-icon name="logout" class="text-gray-400" />
                        {{ __('Log Out') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
