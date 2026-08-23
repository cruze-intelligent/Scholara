<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Academic Trends') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card title="Average score by subject, per term">
                @forelse ($bySubjectTerm as $subject => $terms)
                    <div class="mb-5 last:mb-0">
                        <p class="font-medium text-gray-800 mb-2">{{ $subject }}</p>
                        @foreach ($terms as $row)
                            <div class="flex items-center gap-3 mb-1.5">
                                <span class="text-sm text-gray-500 w-28 shrink-0">{{ $row->term }}</span>
                                <div class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ round($row->avg_score) }}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-700 w-20 text-right">
                                    {{ round($row->avg_score, 1) }}% <span class="text-gray-400 font-normal">({{ $row->student_count }})</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="text-gray-500">No assessment scores recorded yet.</p>
                @endforelse
            </x-card>

            <x-card title="Students below 60% average">
                @forelse ($atRisk as $row)
                    <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                        <span>{{ $row->first_name }} {{ $row->last_name }}</span>
                        <span class="font-medium text-red-600">{{ round($row->avg_score, 1) }}%</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No students currently averaging below 60%.</p>
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
