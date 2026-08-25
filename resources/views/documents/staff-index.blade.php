<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Staff Documents') }} — {{ $staffUser->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="text-sm font-medium text-green-600">{{ session('status') }}</p>
            @endif

            @if (auth()->user()->hasAnyRole(['hr', 'admin']))
                <x-card>
                    <form method="POST" action="{{ route('users.documents.store', $staffUser) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <x-form.input name="title" label="Title" placeholder="e.g. Signed employment contract" />
                        <div>
                            <label class="block text-sm font-medium text-gray-700">File</label>
                            <input type="file" name="file" required
                                class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                            <p class="text-xs text-gray-400 mt-1">PDF, Word, or image — up to 10MB.</p>
                            @error('file')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-primary-button>{{ __('Upload') }}</x-primary-button>
                    </form>
                </x-card>
            @endif

            <x-card>
                @forelse ($documents as $document)
                    <div class="flex justify-between items-start border-b border-gray-100 py-3 last:border-0">
                        <div>
                            <p class="font-medium">{{ $document->title }}</p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $document->uploader->name }} &middot; {{ $document->created_at->format('d M Y') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('documents.download', $document) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Download</a>
                            @if (auth()->user()->hasAnyRole(['hr', 'admin']))
                                <form method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Delete this document?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-medium text-red-500 hover:text-red-700">Delete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <x-empty-state message="No documents on file yet." />
                @endforelse
            </x-card>
        </div>
    </div>
</x-app-layout>
