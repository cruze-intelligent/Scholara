<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Students') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="GET" action="{{ route('students.index') }}" class="flex gap-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by name or admission number…"
                    class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                <x-primary-button type="submit">{{ __('Search') }}</x-primary-button>
            </form>

            <x-card>
                @forelse ($students as $student)
                    <a href="{{ route('students.show', $student) }}" class="flex items-center gap-3 border-b border-gray-100 py-3 last:border-0 hover:bg-gray-50 -mx-2 px-2 rounded-lg transition-colors">
                        @if ($student->photo_url)
                            <img src="{{ $student->photo_url }}" alt="" class="h-10 w-10 rounded-full object-cover">
                        @else
                            <div class="h-10 w-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-sm font-semibold">
                                {{ collect(explode(' ', $student->full_name))->map(fn ($n) => $n[0] ?? '')->take(2)->implode('') }}
                            </div>
                        @endif
                        <div class="flex-1">
                            <p class="font-medium text-gray-800">{{ $student->full_name }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $student->admission_no }} &middot; {{ $student->schoolClass?->name ?? 'No class' }}
                            </p>
                        </div>
                    </a>
                @empty
                    <x-empty-state message="No students found." />
                @endforelse
            </x-card>

            {{ $students->links() }}
        </div>
    </div>
</x-app-layout>
