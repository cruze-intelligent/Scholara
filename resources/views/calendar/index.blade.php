<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Academic Calendar') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            @hasrole('admin')
                <div class="text-right">
                    <a href="{{ route('calendar.create') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700 shadow-sm shadow-indigo-600/20 transition-colors">
                        + Add date
                    </a>
                </div>
            @endhasrole

            @php
                $badgeColors = [
                    'term_start' => 'green',
                    'term_end' => 'yellow',
                    'holiday' => 'blue',
                    'exam_period' => 'red',
                    'deadline' => 'yellow',
                    'event' => 'gray',
                ];
            @endphp

            <x-card title="Upcoming">
                @forelse ($upcoming as $event)
                    <div class="border-b border-gray-100 py-3 last:border-0 flex justify-between items-start gap-3">
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800">{{ $event->title }}</p>
                            <p class="text-sm text-gray-500">
                                {{ $event->start_date->format('D, j M Y') }}
                                @if ($event->end_date && ! $event->end_date->equalTo($event->start_date))
                                    &ndash; {{ $event->end_date->format('D, j M Y') }}
                                @endif
                            </p>
                            @if ($event->description)
                                <p class="text-sm text-gray-500 mt-1">{{ $event->description }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <x-badge :color="$badgeColors[$event->category] ?? 'gray'">{{ \App\Models\CalendarEvent::CATEGORIES[$event->category] }}</x-badge>
                            @hasrole('admin')
                                <a href="{{ route('calendar.edit', $event) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                            @endhasrole
                        </div>
                    </div>
                @empty
                    <x-empty-state message="No upcoming dates on the calendar yet." />
                @endforelse
            </x-card>

            @if ($past->isNotEmpty())
                <x-card title="Past">
                    @foreach ($past as $event)
                        <div class="border-b border-gray-100 py-3 last:border-0 flex justify-between items-start gap-3 opacity-60">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800">{{ $event->title }}</p>
                                <p class="text-sm text-gray-500">
                                    {{ $event->start_date->format('D, j M Y') }}
                                    @if ($event->end_date && ! $event->end_date->equalTo($event->start_date))
                                        &ndash; {{ $event->end_date->format('D, j M Y') }}
                                    @endif
                                </p>
                            </div>
                            <x-badge :color="$badgeColors[$event->category] ?? 'gray'">{{ \App\Models\CalendarEvent::CATEGORIES[$event->category] }}</x-badge>
                        </div>
                    @endforeach
                </x-card>
            @endif
        </div>
    </div>
</x-app-layout>
