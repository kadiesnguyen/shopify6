@php
    $actions = config('portal.quick_actions', []);
@endphp

{{-- Reference: 5 white squircle cards with colorful image icons --}}
<div class="flex items-stretch justify-between px-2 pt-5">
    @foreach ($actions as $action)
        <a
            href="{{ route($action['route']) }}"
            class="flex min-w-0 flex-1 flex-col items-center no-underline active:opacity-80"
        >
            <span class="flex size-[54px] items-center justify-center rounded-[15px] bg-white shadow-sm ring-1 ring-gray-100">
                <img src="{{ asset($action['image']) }}" alt="" class="size-[38px] rounded-[11px] object-contain" loading="lazy">
            </span>
            <span class="mt-1.5 px-0.5 text-center text-[13px] leading-tight text-[#242323]">
                {{ __($action['label_key']) }}
            </span>
        </a>
    @endforeach
</div>
