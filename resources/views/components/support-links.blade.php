{{-- Icon-only support contact — no email/phone text rendered anywhere on the page, just the two
     icons; the actual address/number only ever appear in the non-visible href/title attributes. --}}
@props(['class' => 'text-gray-400'])
<div {{ $attributes->merge(['class' => 'flex items-center gap-1']) }}>
    <a href="mailto:admin@scholara.site" title="Email support" aria-label="Email support"
        class="p-2 rounded-lg {{ $class }} hover:text-indigo-600 hover:bg-gray-900/5 transition-colors duration-150">
        <x-nav-icon name="mail" class="h-5 w-5" />
    </a>
    <a href="https://wa.me/256758401626" target="_blank" rel="noopener" title="WhatsApp support" aria-label="WhatsApp support"
        class="p-2 rounded-lg {{ $class }} hover:text-green-600 hover:bg-gray-900/5 transition-colors duration-150">
        <x-nav-icon name="whatsapp" class="h-5 w-5" />
    </a>
</div>
