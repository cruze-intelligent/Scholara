<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('School Settings') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            <x-card>
                <p class="font-semibold text-gray-800 mb-1">{{ $school->name }}</p>
                <p class="text-sm text-gray-500 mb-6">
                    Choose the levels {{ $school->name }} actually runs — modules that only apply
                    to a level you don't offer (like Nursery daily logs) are hidden from
                    everyone's navigation once it's unchecked here.
                </p>

                <form method="POST" action="{{ route('school-settings.update') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="pb-4 border-b border-gray-100">
                        <x-input-label value="School logo" />
                        <p class="text-xs text-gray-500 mt-0.5 mb-2">
                            Appears on every generated document — report cards, payslips, receipts.
                        </p>
                        @if ($school->logo_url)
                            <div class="flex items-center gap-3 mb-2">
                                <img src="{{ $school->logo_url }}" alt="" class="h-14 w-14 rounded-lg object-contain ring-1 ring-gray-200 bg-white">
                                <label class="flex items-center gap-2 text-sm text-red-600">
                                    <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                                    Remove logo
                                </label>
                            </div>
                        @endif
                        <x-form.input name="logo" type="file" accept="image/*" />
                    </div>

                    <div class="space-y-2">
                        @foreach ($availableLevels as $level)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="levels[]" value="{{ $level }}"
                                    @checked(collect(old('levels', $school->levels()))->contains($level))
                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                {{ ucwords(str_replace('_', ' ', $level)) }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('levels')" class="mt-1" />

                    <x-primary-button type="submit">{{ __('Save') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
