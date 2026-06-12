<button
    type="submit"
    {{ $attributes->class([
        'inline-flex h-11 min-w-[3.75rem] shrink-0 items-center justify-center rounded-xl bg-emerald-600 px-3.5 text-sm font-semibold leading-none text-white hover:bg-emerald-700 disabled:opacity-60',
    ]) }}
>
    {{ __('chat.send') }}
</button>
