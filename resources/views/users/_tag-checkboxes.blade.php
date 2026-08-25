{{-- Distinction tags — shown only for whichever base role is currently selected. Shared between
     create.blade.php and edit.blade.php so both stay in sync as tags are added. --}}
@foreach ($tagsByRole as $roleKey => $tags)
    <div x-show="role === '{{ $roleKey }}'" x-cloak class="border-t border-gray-100 pt-4">
        <x-input-label value="Additional responsibilities (optional)" />
        <p class="text-xs text-gray-500 mt-0.5">These can be changed later if the assignment changes.</p>
        <div class="mt-2 space-y-1.5">
            @foreach ($tags as $tagKey => $tagLabel)
                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="tags[]" value="{{ $tagKey }}"
                        @checked(collect(old('tags', $currentTags ?? []))->contains($tagKey))
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    {{ $tagLabel }}
                </label>
            @endforeach
        </div>
    </div>
@endforeach
