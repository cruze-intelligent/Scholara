<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit') }} {{ $student->full_name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <x-card>
                <form method="POST" action="{{ route('students.update', $student) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PUT')

                    <div class="grid grid-cols-2 gap-4">
                        <x-form.input name="first_name" label="First name" :value="$student->first_name" />
                        <x-form.input name="last_name" label="Last name" :value="$student->last_name" />
                        <x-form.input name="dob" label="Date of birth" type="date" :value="optional($student->dob)->toDateString()" />
                        <x-form.select name="gender" label="Gender" :options="['male' => 'Male', 'female' => 'Female']" :selected="$student->gender" />
                        <x-form.select name="curriculum_level" label="Curriculum level"
                            :options="['nursery' => 'Nursery', 'primary' => 'Primary', 'lower_secondary' => 'Lower Secondary', 'upper_secondary' => 'Upper Secondary']"
                            :selected="$student->curriculum_level" />
                        <x-form.select name="school_class_id" label="Class"
                            :options="collect(['' => '—'])->merge($classes->pluck('name', 'id'))" :selected="$student->school_class_id" />
                        <x-form.select name="stream_id" label="Stream (optional)"
                            :options="collect(['' => '—'])->merge($streams->pluck('name', 'id'))" :selected="$student->stream_id" />
                        <div class="col-span-2"><x-form.input name="photo" label="Photo (optional)" type="file" accept="image/*" /></div>
                    </div>

                    <x-primary-button>{{ __('Save') }}</x-primary-button>
                </form>
            </x-card>

            <x-card title="Guardians">
                @forelse ($student->guardians as $guardian)
                    <div class="border-b border-gray-100 py-2 last:border-0 text-sm">
                        <p class="font-medium text-gray-800">{{ $guardian->user->name }}</p>
                        <p class="text-xs text-gray-500">
                            {{ $guardian->user->email ?? $guardian->user->phone }} &middot; {{ $guardian->relationship_to_student }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 mb-4">No guardian linked yet.</p>
                @endforelse

                <form method="POST" action="{{ route('students.guardians.store', $student) }}" class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-100">
                    @csrf
                    <div class="col-span-2"><x-form.input name="guardian_name" label="Add a guardian — name" /></div>
                    <x-form.input name="guardian_phone" label="Phone" />
                    <x-form.input name="guardian_email" label="Email" type="email" />
                    <div class="col-span-2"><x-form.input name="relationship_to_student" label="Relationship (optional)" placeholder="e.g. Father" /></div>
                    <div class="col-span-2"><x-primary-button type="submit">{{ __('Add guardian') }}</x-primary-button></div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
