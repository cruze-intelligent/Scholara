@props(['label' => null, 'name', 'value' => null])
<div>
    @if ($label)
        <x-input-label :for="$name" :value="$label" />
    @endif
    <textarea id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm', 'rows' => 3]) }}>{{ old($name, $value) }}</textarea>
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
