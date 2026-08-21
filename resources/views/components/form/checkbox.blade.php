@props(['label' => null, 'name', 'checked' => false])
<label for="{{ $name }}" class="flex items-center gap-2 text-sm text-gray-700">
    <input type="checkbox" id="{{ $name }}" name="{{ $name }}" value="1"
        {{ $attributes->merge(['class' => 'rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500']) }}
        @checked(old($name, $checked))>
    {{ $label }}
</label>
