@props([
    'name',
    'label',
    'value' => '',
    'hint' => null,
])

<div>
    <label class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>
    @if ($hint)
        <p class="mb-2 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    <input type="hidden" id="{{ $name }}" name="{{ $name }}" value="{{ old($name, $value) }}">

    <div
        data-rich-editor
        data-input="{{ $name }}"
        class="admin-rich-editor rounded-lg border border-slate-200 bg-white"
    ></div>

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
