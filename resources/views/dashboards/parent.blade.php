<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Guardian Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @forelse ($students as $student)
                <x-card>
                    <div class="flex items-center gap-4 mb-4">
                        @if ($student->photo_url)
                            <img src="{{ $student->photo_url }}" alt="" class="h-14 w-14 rounded-full object-cover ring-2 ring-white shadow-sm">
                        @else
                            <div class="h-14 w-14 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold ring-2 ring-white shadow-sm">
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

                    <div class="flex justify-between items-center mb-2">
                        <p class="text-sm font-medium text-gray-500">Recent assessment scores</p>
                        <a href="{{ route('students.report-card', $student) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">
                            Report card (PDF)
                        </a>
                    </div>
                    @forelse ($student->assessmentScores->take(5) as $score)
                        <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                            <span class="text-gray-700">{{ $score->assessment->type }}</span>
                            <span class="font-medium text-gray-800">{{ $score->raw_score }} / {{ $score->assessment->max_score }}</span>
                        </div>
                    @empty
                        <p class="text-gray-400 text-sm mb-4">No scores recorded yet.</p>
                    @endforelse

                    @if ($student->subjectPredictions->isNotEmpty())
                        <p class="text-sm font-medium text-gray-500 mt-4 mb-2">Performance trend</p>
                        @foreach ($student->subjectPredictions as $prediction)
                            <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                                <span class="text-gray-700">{{ $prediction['subject']->name }}</span>
                                <span class="font-medium text-gray-800">{{ $prediction['predicted'] !== null ? round($prediction['predicted'], 1) : '—' }}</span>
                            </div>
                        @endforeach
                    @endif

                    <p class="text-sm font-medium text-gray-500 mt-4 mb-2">Invoices</p>
                    @forelse ($student->invoices as $invoice)
                        <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between items-center text-sm">
                            <span class="text-gray-700">{{ $invoice->term }}</span>
                            <span class="flex items-center gap-3">
                                <span class="text-gray-800">{{ number_format($invoice->amount_due, 0) }}</span>
                                <x-badge :color="match($invoice->status) { 'paid' => 'green', 'partially_paid' => 'yellow', default => 'red' }">
                                    {{ str_replace('_', ' ', $invoice->status) }}
                                </x-badge>
                                @if ($invoice->status !== 'paid')
                                    <a href="{{ route('invoices.pay', $invoice) }}" class="font-medium text-indigo-600 hover:text-indigo-800">
                                        {{ __('Pay') }}
                                    </a>
                                @endif
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-400 text-sm">No invoices yet.</p>
                    @endforelse
                </x-card>
            @empty
                <x-card>
                    <x-empty-state message="No students linked to your account yet." />
                </x-card>
            @endforelse
        </div>
    </div>
</x-app-layout>
