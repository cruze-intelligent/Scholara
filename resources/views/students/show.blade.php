<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $student->full_name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <x-card>
                <div class="flex items-center gap-4">
                    @if ($student->photo_url)
                        <img src="{{ $student->photo_url }}" alt="" class="h-16 w-16 rounded-full object-cover ring-2 ring-white shadow-sm">
                    @else
                        <div class="h-16 w-16 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold text-lg ring-2 ring-white shadow-sm">
                            {{ collect(explode(' ', $student->full_name))->map(fn ($n) => $n[0] ?? '')->take(2)->implode('') }}
                        </div>
                    @endif
                    <div>
                        <p class="font-semibold text-gray-800 text-lg">{{ $student->full_name }}</p>
                        <p class="text-sm text-gray-500">
                            {{ $student->admission_no }} &middot; {{ $student->schoolClass?->name ?? 'No class' }}
                            &middot; {{ ucfirst(str_replace('_', ' ', $student->curriculum_level)) }}
                        </p>
                    </div>
                </div>

                @if ($student->tags->isNotEmpty())
                    <div class="flex flex-wrap gap-2 mt-4">
                        @foreach ($student->tags as $tag)
                            <span class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-amber-200">
                                {{ str_replace('_', ' ', ucfirst($tag->tag)) }}
                                @if ($tag->note) <span class="text-amber-500">— {{ $tag->note }}</span> @endif
                                @if ($tag->tagged_by === auth()->id() || auth()->user()->hasRole('admin'))
                                    <form method="POST" action="{{ route('students.tags.destroy', $tag) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button class="text-amber-400 hover:text-amber-700">&times;</button>
                                    </form>
                                @endif
                            </span>
                        @endforeach
                    </div>
                @endif

                @if ($availableTags->isNotEmpty())
                    <form method="POST" action="{{ route('students.tags.store', $student) }}" class="flex flex-wrap items-end gap-2 mt-4 pt-4 border-t border-gray-100">
                        @csrf
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Add a flag</label>
                            <select name="tag" class="rounded-lg border-gray-300 shadow-sm text-sm">
                                @foreach ($availableTags as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input type="text" name="note" placeholder="Note (optional)" class="rounded-lg border-gray-300 shadow-sm text-sm flex-1 min-w-[10rem]">
                        <x-secondary-button type="submit">{{ __('Add') }}</x-secondary-button>
                    </form>
                @endif
            </x-card>

            @if ($performance)
                <x-card title="Performance over time">
                    @forelse ($performance as $row)
                        <div class="mb-4 last:mb-0">
                            <div class="flex items-center gap-3 mb-1.5">
                                <span class="text-sm font-medium text-gray-700 w-28 shrink-0">{{ $row['term'] }}</span>
                                <div class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ round($row['average'] ?? 0) }}%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-700 w-16 text-right">
                                    {{ $row['average'] !== null ? round($row['average'], 1).'%' : '—' }}
                                </span>
                            </div>
                            <div class="pl-[7.75rem] flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                @foreach ($row['subjects'] as $subject => $score)
                                    <span>{{ $subject }}: {{ $score !== null ? round($score, 1).'%' : '—' }}</span>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <x-empty-state message="No assessment scores recorded yet." />
                    @endforelse
                </x-card>
            @endif

            <x-card title="Records">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <a href="{{ route('students.report-card', $student) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Report card (PDF)</a>
                    @if (auth()->user()->hasAnyRole(['nurse', 'admin']))
                        <a href="{{ route('health-records.edit', $student) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Health record</a>
                        <a href="{{ route('students.documents.index', $student) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">Medical documents</a>
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</x-app-layout>
