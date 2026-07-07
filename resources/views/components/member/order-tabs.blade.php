@props([
    'status' => '',
    'statusCounts' => collect(),
    'routeName' => 'member.orders.index',
    'query' => [],
    'showPendingPayment' => true,
    'tabLabels' => [],
    'hiddenTabs' => [],
])

@php
    $tabs = collect([
        '' => __('member.orders.all_orders'),
        'pending_payment' => __('member.orders.pending_payment'),
        'awaiting_pickup' => __('member.orders.awaiting_pickup'),
        'waiting_shipment' => __('member.orders.waiting_shipment'),
        'shipped' => __('member.orders.shipped'),
        'received' => __('member.orders.received'),
        'completed' => __('member.orders.completed'),
        'cancelled' => __('member.orders.cancelled'),
    ]);

    if (! $showPendingPayment) {
        $tabs = $tabs->except('pending_payment');
    }

    if ($hiddenTabs !== []) {
        $tabs = $tabs->except($hiddenTabs);
    }
@endphp

<div
    class="portal-order-tabs-wrap"
    x-data="{
        dragging: false,
        dragged: false,
        startX: 0,
        startScroll: 0,
        centerActive() {
            const track = this.$refs.track;
            const active = track?.querySelector('[data-order-tab-active]');
            if (! track || ! active) return;
            const left = active.offsetLeft - (track.clientWidth / 2) + (active.offsetWidth / 2);
            track.scrollLeft = Math.max(0, left);
        },
        onMouseDown(event) {
            if (event.button !== 0) return;
            this.dragging = true;
            this.dragged = false;
            this.startX = event.pageX;
            this.startScroll = this.$refs.track.scrollLeft;
            this.$refs.track.classList.add('is-dragging');
            event.preventDefault();
        },
        onMouseMove(event) {
            if (! this.dragging) return;
            event.preventDefault();
            const delta = event.pageX - this.startX;
            if (Math.abs(delta) > 3) this.dragged = true;
            this.$refs.track.scrollLeft = this.startScroll - delta;
        },
        onMouseUp() {
            if (! this.dragging) return;
            this.dragging = false;
            this.$refs.track.classList.remove('is-dragging');
        },
        onTabClick(event) {
            if (this.dragged) {
                event.preventDefault();
                this.dragged = false;
            }
        },
    }"
    x-init="$nextTick(() => centerActive())"
    @mouseup.window="onMouseUp()"
    @mousemove.window="onMouseMove($event)"
>
    <div x-ref="track" class="portal-order-tabs">
        @foreach ($tabs as $key => $label)
            @php
                $label = $tabLabels[$key] ?? $label;
            @endphp
            <a
                href="{{ route($routeName, collect($query)->merge([
                    'status' => $key ?: null,
                    'q' => request('q'),
                    'sort' => request('sort'),
                ])->filter(fn ($value) => $value !== null && $value !== '')->all()) }}"
                @if ($status === $key) data-order-tab-active @endif
                @mousedown="onMouseDown($event)"
                @click="onTabClick($event)"
                @dragstart.prevent
                @class([
                    'portal-order-tabs__tab whitespace-nowrap rounded-lg border px-3.5 py-1.5 text-sm font-medium no-underline transition select-none',
                    'border-red-500 bg-red-500 text-white' => $status === $key,
                    'border-gray-200 bg-white text-gray-600 shadow-sm' => $status !== $key,
                ])
            >
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
