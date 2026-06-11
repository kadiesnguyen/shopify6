@props([
    'name',
    'label',
    'preview' => null,
])

<div {{ $attributes->merge(['class' => 'min-w-0 rounded-lg border border-slate-200 bg-white p-3']) }}>
    <p class="mb-2 text-sm font-medium leading-snug text-slate-700">{{ $label }}</p>

    <div class="mb-3 aspect-[4/3] w-full overflow-hidden rounded-md border border-slate-200 bg-slate-50">
        @if ($preview)
            <img src="{{ $preview }}" alt="" class="h-full w-full object-cover">
        @else
            <div class="flex h-full items-center justify-center px-2 text-center text-xs text-slate-400">
                {{ __('admin.users.actions.no_image') }}
            </div>
        @endif
    </div>

    <label class="block cursor-pointer">
        <span class="inline-flex w-full items-center justify-center whitespace-nowrap rounded-md bg-brand px-3 py-2 text-xs font-semibold text-white hover:bg-brand-dark">
            {{ __('admin.users.actions.choose_image') }}
        </span>
        <input type="file" name="{{ $name }}" accept="image/*" class="hidden">
    </label>
</div>
