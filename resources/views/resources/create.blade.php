<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Upload Teaching Resource') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-card>
                @if ($assignments->isEmpty())
                    <p class="text-gray-500">
                        You have no subject/class assignments yet — ask an admin to assign you a subject to teach
                        before uploading a resource.
                    </p>
                @else
                    <form method="POST" action="{{ route('resources.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <x-form.select name="assignment_id" label="Subject / Class"
                            :options="$assignments->mapWithKeys(fn ($a) => [$a->id => \"{$a->subject->name} — {$a->schoolClass->name}\"])" />

                        <x-form.input name="title" label="Title" placeholder="e.g. Week 4 fractions worksheet" />

                        <div>
                            <label class="block text-sm font-medium text-gray-700">File</label>
                            <input type="file" name="file" required
                                class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                            <p class="text-xs text-gray-400 mt-1">PDF, Word, PowerPoint, Excel, or image — up to 10MB.</p>
                            @error('file')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-primary-button>{{ __('Upload') }}</x-primary-button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>
</x-app-layout>
