<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Import Students') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                <p class="text-sm text-gray-600 mb-4">
                    CSV with a header row: <code class="bg-gray-100 px-1 rounded">{{ implode(', ', $header) }}</code>.
                    <code>curriculum_level</code> must be one of nursery, primary, lower_secondary, upper_secondary.
                    <code>school_class</code> should match an existing class name exactly (optional — leave blank to
                    assign later). Imported students aren't linked to a guardian yet; link them from a parent's
                    account the same way you would a manually-added student.
                </p>

                <form method="POST" action="{{ route('students.import.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">CSV file</label>
                        <input type="file" name="file" accept=".csv,text/csv" required
                            class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                        @error('file')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <x-primary-button>{{ __('Import') }}</x-primary-button>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>
