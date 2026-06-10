@extends('layouts.member')

@section('title', $fromCheckout ? __('member.shipping.checkout_title') : __('member.shipping.title'))
@section('hide_portal_header', $fromCheckout ? '1' : null)
@section('full_bleed', $fromCheckout ? '1' : null)
@section('back_url', $fromCheckout ? null : route('member.my.index'))

@section('content')
    @if ($fromCheckout)
        <header class="sticky top-0 z-10 flex items-center bg-black px-4 py-3 text-white">
            <a href="{{ $redirect ?: route('member.my.index') }}" class="flex items-center gap-1.5">
                <x-member.icon name="chevron-left" class="size-5" />
                <span class="text-sm">{{ __('member.back') }}</span>
            </a>
            <h1 class="absolute left-1/2 -translate-x-1/2 text-base font-semibold">{{ __('member.shipping.checkout_title') }}</h1>
        </header>
    @endif

    @if (session('status'))
        <div @class(['mx-4 mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800', 'mt-3' => $fromCheckout])>
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div @class(['mx-4 mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600', 'mt-3' => $fromCheckout])>
            {{ $errors->first() }}
        </div>
    @endif

    <div @class(['px-4 py-4 space-y-3', 'pt-2' => $fromCheckout])>
        @forelse ($addresses as $address)
            <article @class([
                'relative overflow-hidden rounded-xl border bg-white p-4 shadow-sm',
                'border-emerald-500 ring-1 ring-emerald-500' => $address->is_default,
                'border-gray-200' => ! $address->is_default,
            ])>
                <div class="flex items-start gap-3">
                    @if ($fromCheckout && $redirect)
                        <form method="POST" action="{{ route('member.shipping.select', $address) }}" class="min-w-0 flex-1 text-left">
                            @csrf
                            <input type="hidden" name="redirect" value="{{ $redirect }}">
                            <button type="submit" class="w-full text-left">
                                <p class="font-semibold text-gray-900">{{ $address->recipient_name }} · {{ $address->phone }}</p>
                                <p class="mt-1 text-sm text-gray-600">
                                    {{ collect([$address->address_line, $address->city, $address->state, $address->country])->filter()->implode(', ') }}
                                </p>
                                @if ($address->is_default)
                                    <span class="mt-2 inline-block rounded bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('member.shipping.default') }}</span>
                                @endif
                            </button>
                        </form>
                    @else
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-gray-900">{{ $address->recipient_name }}</p>
                            <p class="text-sm text-gray-600">{{ $address->phone }}</p>
                            <p class="mt-1 text-sm text-gray-600">
                                {{ collect([$address->address_line, $address->city, $address->state, $address->country])->filter()->implode(', ') }}
                            </p>
                            @if ($address->is_default)
                                <span class="mt-2 inline-block rounded bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">{{ __('member.shipping.default') }}</span>
                            @endif
                        </div>
                    @endif

                    @if (! $fromCheckout)
                        <form method="POST" action="{{ route('member.shipping.destroy', $address) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600" aria-label="{{ __('member.shipping.delete') }}">×</button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <x-ui.empty-state :title="__('member.shipping.empty')" class="rounded-xl bg-white" />
        @endforelse
    </div>

    <div @class(['px-4 pb-8', 'pb-28' => $fromCheckout])>
        <a
            href="{{ route('member.shipping.create', array_filter(['redirect' => $redirect])) }}"
            class="flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-emerald-500 bg-emerald-50 py-4 text-sm font-semibold text-emerald-700 active:bg-emerald-100"
        >
            <x-member.icon name="plus" class="size-4" />
            {{ __('member.shipping.add_new') }}
        </a>
    </div>

    @unless ($fromCheckout)
        <form method="POST" action="{{ route('member.shipping.store') }}" class="mx-4 mb-8 rounded-xl bg-white p-4 shadow-sm">
            @csrf
            @if (! empty($redirect))
                <input type="hidden" name="redirect" value="{{ $redirect }}">
            @endif
            <h2 class="mb-4 font-semibold text-slate-900">{{ __('member.shipping.add') }}</h2>
            <div class="space-y-3 text-sm">
                <div>
                    <input name="recipient_name" value="{{ old('recipient_name') }}" placeholder="{{ __('member.shipping.recipient') }}" required class="w-full rounded-lg border-slate-300">
                    @error('recipient_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <input name="phone" value="{{ old('phone') }}" placeholder="{{ __('member.profile.phone') }}" required class="w-full rounded-lg border-slate-300">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <input name="country" value="{{ old('country', 'Việt Nam') }}" placeholder="{{ __('member.shipping.country_placeholder') }}" required class="w-full rounded-lg border-slate-300">
                    @error('country')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <input name="state" value="{{ old('state') }}" placeholder="{{ __('member.shipping.state_placeholder') }}" required class="w-full rounded-lg border-slate-300">
                    @error('state')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <input name="city" value="{{ old('city') }}" placeholder="{{ __('member.shipping.city_placeholder') }}" required class="w-full rounded-lg border-slate-300">
                    @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <input name="address_line" value="{{ old('address_line') }}" placeholder="{{ __('member.shipping.address') }}" required class="w-full rounded-lg border-slate-300">
                    @error('address_line')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <label class="flex items-center gap-2"><input type="checkbox" name="is_default" value="1" checked> {{ __('member.shipping.default') }}</label>
                <button type="submit" class="w-full rounded-lg bg-brand py-2.5 font-semibold text-white">{{ __('member.shipping.save_address') }}</button>
            </div>
        </form>
    @endunless
@endsection
