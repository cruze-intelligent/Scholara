<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Academic Trends') }}</h2>
            <x-pin-toggle pin-key="reports.academics" />
        </div>
    </x-slot>

    @php
        $allRows = $bySubjectTerm->flatten(1);
        $schoolAverage = $allRows->isNotEmpty() ? $allRows->avg('avg_score') : null;
        $bandColor = fn ($score) => match (true) {
            $score >= 80 => 'bg-green-500',
            $score >= 60 => 'bg-amber-500',
            default => 'bg-red-500',
        };
    @endphp

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <x-stat-tile label="School average" :value="$schoolAverage !== null ? round($schoolAverage, 1).'%' : '—'" icon="chart-bar" tone="indigo" />
                <x-stat-tile label="Subjects tracked" :value="$bySubjectTerm->count()" icon="book-open" tone="indigo" />
                <x-stat-tile label="Students below 60%" :value="$atRisk->count()" icon="exclamation-triangle" :tone="$atRisk->count() > 0 ? 'red' : 'green'" />
            </div>

            <x-card title="Average score by subject, per term">
                @forelse ($bySubjectTerm as $subject => $terms)
                    <div class="mb-5 last:mb-0">
                        <p class="font-medium text-gray-800 mb-2">{{ $subject }}</p>
                        @foreach ($terms as $row)
                            <div class="flex items-center gap-3 mb-1.5">
                                <span class="text-sm text-gray-500 w-28 shrink-0">{{ $row->term }}</span>
                                <div class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full {{ $bandColor($row->avg_score) }} rounded-full transition-all" style="width: {{ round($row->avg_score) }}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-700 w-20 text-right">
                                    {{ round($row->avg_score, 1) }}% <span class="text-gray-400 font-normal">({{ $row->student_count }})</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <x-empty-state message="No assessment scores recorded yet." />
                @endforelse
            </x-card>

            <x-card title="Students below 60% average">
                @forelse ($atRisk as $row)
                    <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between items-center text-sm">
                        <span class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full bg-red-500"></span>
                            {{ $row->first_name }} {{ $row->last_name }}
                        </span>
                        <span class="font-medium text-red-600">{{ round($row->avg_score, 1) }}%</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No students currently averaging below 60%.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
