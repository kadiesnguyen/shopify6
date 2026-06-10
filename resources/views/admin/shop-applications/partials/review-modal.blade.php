@props(['item'])

<div
    x-show="reviewId === {{ $item->id }}"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/60 p-0 sm:items-center sm:p-4"
    role="dialog"
    aria-modal="true"
    @click.self="reviewId = null"
    @keydown.escape.window="reviewId = null"
>
    <div
        x-show="reviewId === {{ $item->id }}"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
        class="flex max-h-[94vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl"
        @click.stop
    >
        {{-- Header --}}
        <div class="border-b border-slate-100 bg-slate-50/80 px-5 py-4 sm:px-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-semibold text-slate-900">{{ __('admin.shop_applications.detail_title') }}</h3>
                        <span @class([
                            'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                            'bg-amber-100 text-amber-800' => $item->status === 'pending',
                            'bg-emerald-100 text-emerald-800' => $item->status === 'approved',
                            'bg-red-100 text-red-800' => $item->status === 'rejected',
                        ])>{{ __('admin.shop_applications.status_'.$item->status) }}</span>
                    </div>
                    <p class="mt-1 truncate text-sm text-slate-500">
                        {{ $item->user?->email }}
                        <span class="mx-1 text-slate-300">·</span>
                        {{ $item->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>
                <button
                    type="button"
                    @click="reviewId = null"
                    class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-200/60 hover:text-slate-600"
                    aria-label="{{ __('admin.actions.cancel') }}"
                >
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="flex-1 overflow-y-auto px-5 py-5 sm:px-6">
            <section class="mb-6">
                <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.shop_applications.section_shop_info') }}</h4>
                <dl class="grid gap-3 rounded-xl border border-slate-100 bg-slate-50/50 p-4 sm:grid-cols-2">
                    @foreach ([
                        [__('admin.shop_applications.shop_name'), $item->shop_name],
                        [__('admin.shop_applications.real_name'), $item->real_name],
                        [__('admin.shop_applications.phone'), $item->phone],
                        [__('admin.shop_applications.type'), __('admin.shop_applications.type_'.$item->seller_type)],
                        [__('admin.shop_applications.id_number'), $item->id_number],
                        [__('admin.shop_applications.referral'), $item->referral_code ?: '—'],
                    ] as [$label, $value])
                        <div class="min-w-0">
                            <dt class="text-xs font-medium text-slate-500">{{ $label }}</dt>
                            <dd class="mt-0.5 break-words text-sm font-medium text-slate-900">{{ $value }}</dd>
                        </div>
                    @endforeach
                    <div class="min-w-0 sm:col-span-2">
                        <dt class="text-xs font-medium text-slate-500">{{ __('admin.shop_applications.address') }}</dt>
                        <dd class="mt-0.5 break-words text-sm font-medium text-slate-900">{{ $item->address }}, {{ $item->country }}</dd>
                    </div>
                </dl>
            </section>

            @if ($item->logo || $item->id_front || $item->id_back)
                <section class="mb-6">
                    <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ __('admin.shop_applications.section_documents') }}</h4>
                    <div class="grid gap-4 sm:grid-cols-3">
                        @foreach ([
                            ['label' => __('admin.shop_applications.logo'), 'path' => $item->logo],
                            ['label' => __('admin.shop_applications.id_front'), 'path' => $item->id_front],
                            ['label' => __('admin.shop_applications.id_back'), 'path' => $item->id_back],
                        ] as $doc)
                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                <p class="border-b border-slate-100 px-3 py-2 text-xs font-medium text-slate-600">{{ $doc['label'] }}</p>
                                @if ($doc['path'])
                                    <a href="{{ $item->documentUrl($doc['path']) }}" target="_blank" rel="noopener" class="group block">
                                        <div class="aspect-[4/3] overflow-hidden bg-slate-100">
                                            <img
                                                src="{{ $item->documentUrl($doc['path']) }}"
                                                alt="{{ $doc['label'] }}"
                                                class="size-full object-cover transition duration-200 group-hover:scale-105"
                                            >
                                        </div>
                                        <p class="px-3 py-2 text-center text-xs font-medium text-brand group-hover:underline">{{ __('admin.shop_applications.view_full_image') }}</p>
                                    </a>
                                @else
                                    <div class="flex aspect-[4/3] items-center justify-center bg-slate-50 text-xs text-slate-400">—</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if ($item->admin_note && $item->status !== 'pending')
                <section class="mb-2 rounded-xl border border-red-100 bg-red-50/60 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-600">{{ __('admin.requests.reject_note_placeholder') }}</p>
                    <p class="mt-1 text-sm text-red-900">{{ $item->admin_note }}</p>
                </section>
            @endif
        </div>

        {{-- Footer --}}
        @if ($item->status === 'pending')
            <div class="border-t border-slate-100 bg-white px-5 py-4 sm:px-6">
                <form method="POST" action="{{ route('admin.shop-applications.reject', $item) }}" class="space-y-3">
                    @csrf
                    <label class="block">
                        <span class="mb-1.5 block text-xs font-medium text-slate-600">{{ __('admin.requests.reject_note_placeholder') }}</span>
                        <textarea
                            name="admin_note"
                            rows="2"
                            placeholder="{{ __('admin.shop_applications.reject_note_hint') }}"
                            class="w-full rounded-lg border-slate-200 text-sm shadow-sm focus:border-brand focus:ring-brand"
                        ></textarea>
                    </label>
                    <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            @click="reviewId = null"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            {{ __('admin.actions.cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700"
                        >
                            {{ __('admin.actions.reject') }}
                        </button>
                        <button
                            type="submit"
                            formaction="{{ route('admin.shop-applications.approve', $item) }}"
                            formmethod="POST"
                            class="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand/90"
                        >
                            {{ __('admin.actions.approve') }}
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="flex justify-end border-t border-slate-100 px-5 py-4 sm:px-6">
                <button
                    type="button"
                    @click="reviewId = null"
                    class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    {{ __('admin.shop_applications.close') }}
                </button>
            </div>
        @endif
    </div>
</div>
