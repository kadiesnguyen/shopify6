@props([
    'id',
    'name' => 'password',
    'label',
    'placeholder' => '',
    'required' => true,
    'value' => '',
])

<div x-data="{ show: false }">
    <label for="{{ $id }}" class="mb-1.5 block text-sm font-medium text-slate-800">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <div class="relative">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            :type="show ? 'text' : 'password'"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            {{ $attributes->merge(['class' => 'auth-field pl-3 pr-10']) }}
        >
        <button
            type="button"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 hover:text-slate-600"
            @click="show = !show"
            aria-label="{{ __('auth_portal.toggle_password') }}"
            tabindex="-1"
        >
            <svg x-show="!show" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
            <svg x-show="show" x-cloak class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
        </button>
    </div>
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
