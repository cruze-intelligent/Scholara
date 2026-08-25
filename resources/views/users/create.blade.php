<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Add User') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="text-sm text-gray-500">
                Looking to add a parent? Guardian accounts are created automatically when you
                <a href="{{ route('students.create') }}" class="text-indigo-600 hover:text-indigo-800">enroll a student</a> —
                this page is for staff and learner accounts.
            </p>

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

                    {{-- Learner: link to their own existing student record --}}
                    <div x-show="role === 'learner'" x-cloak class="border-t border-gray-100 pt-4">
                        <x-form.select name="learner_student_id" label="Student record"
                            :options="$unlinkedStudents->pluck('full_name', 'id')" />
                        <p class="text-sm text-gray-500 mt-1">
                            Only students without a login yet are listed. If the student doesn't exist yet,
                            <a href="{{ route('students.create') }}" class="text-indigo-600 hover:text-indigo-800">enroll them first</a>.
                        </p>
                    </div>

                    @include('users._tag-checkboxes')

                    {{-- Staff roles: employment details --}}
                    <div x-show="['teacher','nurse','hr','bursar','librarian'].includes(role)" x-cloak
                        class="grid grid-cols-2 gap-3 border-t border-gray-100 pt-4">
                        <x-form.input name="trn" label="TRN (teachers)" />
                        <x-form.input name="role_title" label="Job title" />
                        <x-form.input name="hire_date" label="Hire date" type="date" />
                        <x-form.input name="monthly_gross_salary" label="Monthly gross salary" type="number" />
                        <x-form.select name="stream_id" label="Stream (optional)"
                            :options="collect(['' => '—'])->merge($streams->pluck('name', 'id'))" />
                        <x-form.input name="photo" label="ID photo (optional)" type="file" accept="image/*" />
                    </div>

                    <x-primary-button type="submit">{{ __('Create user') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
