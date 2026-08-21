<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Take Attendance') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <x-card>
                <form method="GET" action="{{ route('attendance.create') }}" class="flex gap-4 items-end mb-6">
                    <x-form.select name="class_id" label="Class" class="w-56"
                        :options="$classes->mapWithKeys(fn ($c) => [$c->id => $c->name])" :selected="$class?->id" />
                    <x-form.input name="date" label="Date" type="date" :value="$date->toDateString()" />
                    <x-secondary-button type="submit">{{ __('Load') }}</x-secondary-button>
                </form>

                @if ($class)
                    <form method="POST" action="{{ route('attendance.store') }}" class="space-y-1">
                        @csrf
                        <input type="hidden" name="school_class_id" value="{{ $class->id }}">
                        <input type="hidden" name="date" value="{{ $date->toDateString() }}">

                        @forelse ($class->students as $student)
                            @php $current = $existing->get($student->id)?->status ?? 'present' @endphp
                            <div class="grid grid-cols-2 gap-2 items-center py-2 border-b border-gray-100 last:border-0">
                                <span>{{ $student->full_name }}</span>
                                <div class="flex gap-3 text-sm">
                                    @foreach (['present', 'absent', 'late'] as $status)
                                        <label class="flex items-center gap-1">
                                            <input type="radio" name="status[{{ $student->id }}]" value="{{ $status }}" @checked($current === $status)>
                                            {{ ucfirst($status) }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500">No students in this class yet.</p>
                        @endforelse

                        @if ($class->students->isNotEmpty())
                            <div class="pt-4">
                                <x-primary-button>{{ __('Save attendance') }}</x-primary-button>
                            </div>
                        @endif
                    </form>
                @else
                    <p class="text-gray-500">You have no classes assigned yet.</p>
                @endif
            </x-card>

            <a href="{{ route('attendance.stats') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                View attendance stats &rarr;
            </a>
        </div>
    </div>
</x-app-layout>
