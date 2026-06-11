@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="space-y-3">
        <p class="text-center text-sm text-gray-500">
            @if ($paginator->firstItem())
                {{ __('member.pagination.showing', [
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'total' => $paginator->total(),
                ]) }}
            @else
                {{ __('member.pagination.empty') }}
            @endif
        </p>

        <div class="-mx-1 overflow-x-auto pb-1">
            <div class="flex min-w-max items-center justify-center gap-1 px-1">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-300" aria-disabled="true">
                        <x-member.icon name="chevron-left" class="size-4" />
                    </span>
                @else
                    <a
                        href="{{ $paginator->previousPageUrl() }}"
                        rel="prev"
                        class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-emerald-300 hover:text-emerald-600"
                        aria-label="{{ __('pagination.previous') }}"
                    >
                        <x-member.icon name="chevron-left" class="size-4" />
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex min-w-9 shrink-0 items-center justify-center px-2 text-sm text-gray-400">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span
                                    aria-current="page"
                                    class="inline-flex min-w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white"
                                >
                                    {{ $page }}
                                </span>
                            @else
                                <a
                                    href="{{ $url }}"
                                    class="inline-flex min-w-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:border-emerald-300 hover:text-emerald-600"
                                    aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                >
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a
                        href="{{ $paginator->nextPageUrl() }}"
                        rel="next"
                        class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 transition hover:border-emerald-300 hover:text-emerald-600"
                        aria-label="{{ __('pagination.next') }}"
                    >
                        <x-member.icon name="chevron-right" class="size-4" />
                    </a>
                @else
                    <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-300" aria-disabled="true">
                        <x-member.icon name="chevron-right" class="size-4" />
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
