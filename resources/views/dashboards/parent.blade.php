<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Guardian Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @forelse ($students as $student)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center gap-4 mb-4">
                        @if ($student->photo_url)
                            <img src="{{ $student->photo_url }}" alt="" class="h-14 w-14 rounded-full object-cover">
                        @else
                            <div class="h-14 w-14 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold">
                                {{ collect(explode(' ', $student->full_name))->map(fn ($n) => $n[0] ?? '')->take(2)->implode('') }}
                            </div>
                        @endif
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800">{{ $student->full_name }}</h3>
                            <form method="POST" action="{{ route('students.photo.update', $student) }}" enctype="multipart/form-data"
                                class="flex items-center gap-2 mt-1">
                                @csrf
                                <label class="text-xs font-medium text-indigo-600 hover:text-indigo-800 cursor-pointer">
                                    {{ $student->photo_url ? 'Change photo' : 'Add photo' }}
                                    <input type="file" name="photo" accept="image/*" class="hidden" onchange="this.form.submit()">
                                </label>
                            </form>
                        </div>
                    </div>

                    <p class="text-sm text-gray-500 mb-2">Recent assessment scores</p>
                    @forelse ($student->assessmentScores->take(5) as $score)
                        <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                            <span>{{ $score->assessment->type }}</span>
                            <span>{{ $score->raw_score }} / {{ $score->assessment->max_score }}</span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm mb-4">No scores recorded yet.</p>
                    @endforelse

                    @if ($student->subjectPredictions->isNotEmpty())
                        <p class="text-sm text-gray-500 mt-4 mb-2">Performance trend</p>
                        @foreach ($student->subjectPredictions as $prediction)
                            <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                                <span>{{ $prediction['subject']->name }}</span>
                                <span>{{ $prediction['predicted'] !== null ? round($prediction['predicted'], 1) : '—' }}</span>
                            </div>
                        @endforeach
                    @endif

                    <p class="text-sm text-gray-500 mt-4 mb-2">Invoices</p>
                    @forelse ($student->invoices as $invoice)
                        <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between items-center text-sm">
                            <span>{{ $invoice->term }}</span>
                            <span class="flex items-center gap-3">
                                {{ number_format($invoice->amount_due, 0) }} ({{ $invoice->status }})
                                @if ($invoice->status !== 'paid')
                                    <a href="{{ route('invoices.pay', $invoice) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                        {{ __('Pay') }}
                                    </a>
                                @endif
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">No invoices yet.</p>
                    @endforelse
                </div>
            @empty
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-gray-500">No students linked to your account yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
