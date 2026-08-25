<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Enroll a Student') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <x-card>
                <form method="POST" action="{{ route('students.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <p class="text-sm font-semibold text-gray-700 mb-3">Student</p>
                        <div class="grid grid-cols-2 gap-4">
                            <x-form.input name="first_name" label="First name" />
                            <x-form.input name="last_name" label="Last name" />
                            <x-form.input name="dob" label="Date of birth" type="date" />
                            <x-form.select name="gender" label="Gender" :options="['male' => 'Male', 'female' => 'Female']" />
                            <x-form.select name="curriculum_level" label="Curriculum level"
                                :options="['nursery' => 'Nursery', 'primary' => 'Primary', 'lower_secondary' => 'Lower Secondary', 'upper_secondary' => 'Upper Secondary']" />
                            <x-form.select name="school_class_id" label="Class (optional)"
                                :options="collect(['' => '—'])->merge($classes->pluck('name', 'id'))" />
                            <div class="col-span-2"><x-form.input name="admission_no" label="Admission no. (optional, auto-generated)" /></div>
                            <div class="col-span-2"><x-form.input name="photo" label="Photo (optional)" type="file" accept="image/*" /></div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <p class="text-sm font-semibold text-gray-700 mb-1">Guardian</p>
                        <p class="text-xs text-gray-500 mb-3">
                            A login is created automatically — reused for a sibling if the phone or email
                            already matches an existing guardian at this school.
                        </p>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2"><x-form.input name="guardian_name" label="Guardian name" /></div>
                            <x-form.input name="guardian_phone" label="Guardian phone" />
                            <x-form.input name="guardian_email" label="Guardian email" type="email" />
                            <div class="col-span-2"><x-form.input name="relationship_to_student" label="Relationship (optional)" placeholder="e.g. Mother" /></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">At least one of phone or email is required, so the guardian can log in.</p>
                    </div>

                    <x-primary-button>{{ __('Enroll student') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
