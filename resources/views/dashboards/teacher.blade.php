<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Teacher Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">My classes</h3>
                @forelse ($classes as $class)
                    <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between">
                        <span>{{ $class->name }} ({{ $class->level }})</span>
                        <span class="text-gray-500">{{ $class->students_count }} students</span>
                    </div>
                @empty
                    <p class="text-gray-500">No classes assigned yet.</p>
                @endforelse
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Upcoming lesson plans</h3>
                @forelse ($upcomingLessonPlans as $plan)
                    <div class="border-b border-gray-100 py-2 last:border-0">
                        <p class="font-medium">{{ $plan->date->format('d M Y') }} &mdash; {{ $plan->schoolClass->name }}</p>
                    </div>
                @empty
                    <p class="text-gray-500">No lesson plans scheduled.</p>
                @endforelse
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Support Strategy alerts</h3>
                @forelse ($supportAlerts as $alert)
                    <div class="border-b border-gray-100 py-2 last:border-0 flex justify-between text-sm">
                        <span>{{ $alert['student']->full_name }} &mdash; {{ $alert['subject']->name }}</span>
                        <span class="text-yellow-700">predicted {{ $alert['predicted'] }} (baseline {{ $alert['baseline'] }})</span>
                    </div>
                @empty
                    <p class="text-gray-500">No students currently flagged.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
