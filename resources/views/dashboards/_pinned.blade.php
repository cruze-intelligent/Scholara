{{-- Included at the top of every role's dashboard. $upcomingEvents comes from
     DashboardController::index() regardless of role. --}}
@php
    $pinnedKeys = auth()->user()->pinnedItems->pluck('key');
    $pinnedTabs = collect(\App\Support\PinnableTabs::ITEMS)->only($pinnedKeys->all());
@endphp

@unless ($pinnedKeys->contains('calendar_dismissed'))
    <x-card class="mb-6">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                <x-nav-icon name="calendar" class="text-indigo-500" />
                {{ __('Academic Calendar') }}
            </h3>
            <div class="flex items-center gap-3">
                <a href="{{ route('calendar.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">{{ __('View all') }}</a>
                <form method="POST" action="{{ route('pins.store', 'calendar_dismissed') }}">
                    @csrf
                    <button type="submit" title="{{ __('Remove from dashboard') }}" class="text-gray-300 hover:text-gray-500 leading-none text-lg">&times;</button>
                </form>
            </div>
        </div>
        @forelse ($upcomingEvents as $event)
            <div class="flex items-center justify-between text-sm py-1.5 border-b border-gray-50 last:border-0">
                <span class="text-gray-700">{{ $event->title }}</span>
                <span class="text-gray-400">{{ $event->start_date->format('d M') }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-400">{{ __('No upcoming dates.') }}</p>
        @endforelse
    </x-card>
@endunless

@if ($pinnedTabs->isNotEmpty())
    <div class="flex flex-wrap gap-2 mb-6">
        @foreach ($pinnedTabs as $key => $tab)
            <div class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium">
                <a href="{{ route($key) }}" class="hover:underline">{{ $tab['label'] }}</a>
                <form method="POST" action="{{ route('pins.destroy', $key) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" title="{{ __('Unpin') }}" class="text-indigo-400 hover:text-indigo-700 leading-none">&times;</button>
                </form>
            </div>
        @endforeach
    </div>
@endif
