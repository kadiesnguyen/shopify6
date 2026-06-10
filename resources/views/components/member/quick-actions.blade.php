@php
    $actions = config('portal.quick_actions', []);
@endphp

<div class="flex items-stretch justify-between gap-1 px-0.5 pt-4">
    @foreach ($actions as $action)
        <a
            href="{{ route($action['route']) }}"
            class="flex min-w-0 flex-1 flex-col items-center gap-1.5 rounded-lg py-1 active:opacity-80"
        >
            <span @class([
                'mx-auto flex size-11 shrink-0 items-center justify-center rounded-full text-white shadow-sm ring-1 ring-black/5',
                $action['color'],
            ])>
                <x-member.icon :name="$action['icon']" class="size-[1.15rem]" />
            </span>
            <span class="px-0.5 text-center text-[10px] leading-tight text-gray-900 sm:text-xs">
                {{ __($action['label_key']) }}
            </span>
        </a>
    @endforeach
</div>
