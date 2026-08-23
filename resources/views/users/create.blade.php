<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add User') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data"
                    x-data="{ role: '{{ old('role', '') }}' }" class="space-y-4">
                    @csrf

                    <x-form.input name="name" label="Full name" />
                    <x-form.input name="email" label="Email" type="email" />
                    <x-form.input name="phone" label="Phone (optional)" />

                    <div>
                        <x-input-label for="role" value="Role" />
                        <select id="role" name="role" x-model="role" required
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="" disabled>Choose a role&hellip;</option>
                            @foreach ($roles as $roleOption)
                                <option value="{{ $roleOption }}" @selected(old('role') === $roleOption)>{{ ucfirst($roleOption) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role')" class="mt-1" />
                    </div>

                    {{-- Parent: link to one or more existing children, or add a new one --}}
                    <div x-show="role === 'parent'" x-cloak class="space-y-4 border-t border-gray-100 pt-4">
                        <x-form.input name="relationship_to_student" label="Relationship (mother, father, guardian&hellip;)" />

                        <div>
                            <x-input-label value="Children at this school" />
                            <div class="mt-2 border border-gray-200 rounded-md divide-y divide-gray-100 max-h-56 overflow-y-auto">
                                @forelse ($students as $student)
                                    <label class="flex items-center gap-2 px-3 py-2 text-sm">
                                        <input type="checkbox" name="child_ids[]" value="{{ $student->id }}"
                                            @checked(collect(old('child_ids'))->contains($student->id))
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                        {{ $student->full_name }}
                                    </label>
                                @empty
                                    <p class="px-3 py-2 text-sm text-gray-500">No students yet — add one below.</p>
                                @endforelse
                            </div>
                            <x-input-error :messages="$errors->get('child_ids')" class="mt-1" />
                        </div>

                        <p class="text-sm text-gray-500">Child not listed? Add them here:</p>
                        <div class="grid grid-cols-2 gap-3">
                            <x-form.input name="new_child_first_name" label="First name" />
                            <x-form.input name="new_child_last_name" label="Last name" />
                            <x-form.input name="new_child_dob" label="Date of birth" type="date" />
                            <x-form.select name="new_child_gender" label="Gender" :options="['male' => 'Male', 'female' => 'Female']" />
                        </div>
                        <x-form.select name="new_child_curriculum_level" label="Level of education"
                            :options="['nursery' => 'Nursery', 'primary' => 'Primary', 'lower_secondary' => 'Lower Secondary', 'upper_secondary' => 'Upper Secondary']" />
                        <p class="text-sm text-gray-500 -mt-2">
                            Determines which modules apply to this student (e.g. nursery daily
                            logs vs. primary/secondary assessments) — editable later from the
                            student's record.
                        </p>
                        <x-form.select name="new_child_school_class_id" label="Class"
                            :options="$classes->pluck('name', 'id')" />
                        <x-form.input name="new_child_photo" label="Photo (optional)" type="file" accept="image/*" />
                    </div>

                    {{-- Learner: link to their own existing student record --}}
                    <div x-show="role === 'learner'" x-cloak class="border-t border-gray-100 pt-4">
                        <x-form.select name="learner_student_id" label="Student record"
                            :options="$unlinkedStudents->pluck('full_name', 'id')" />
                        <p class="text-sm text-gray-500 mt-1">
                            Only students without a login yet are listed. If the student doesn't exist,
                            add them via a parent account first.
                        </p>
                    </div>

                    {{-- Staff roles: employment details --}}
                    <div x-show="['teacher','nurse','hr','bursar','librarian'].includes(role)" x-cloak
                        class="grid grid-cols-2 gap-3 border-t border-gray-100 pt-4">
                        <x-form.input name="trn" label="TRN (teachers)" />
                        <x-form.input name="role_title" label="Job title" />
                        <x-form.input name="hire_date" label="Hire date" type="date" />
                        <x-form.input name="monthly_gross_salary" label="Monthly gross salary" type="number" />
                        <x-form.input name="photo" label="ID photo (optional)" type="file" accept="image/*" />
                    </div>

                    <x-primary-button type="submit">{{ __('Create user') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
