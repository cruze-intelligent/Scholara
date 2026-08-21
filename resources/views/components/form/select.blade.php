@props(['label' => null, 'name', 'options' => [], 'selected' => null])
<div>
    @if ($label)
        <x-input-label :for="$name" :value="$label" />
    @endif
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm']) }}>
        @foreach ($options as $optionValue => $optionText)
            <option value="{{ $optionValue }}" @selected((string) old($name, $selected) === (string) $optionValue)>{{ $optionText }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get($name)" class="mt-1" />
</div>
