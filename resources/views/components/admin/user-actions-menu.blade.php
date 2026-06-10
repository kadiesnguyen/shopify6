@props(['user', 'listQuery' => []])

@php
    $modalUrl = fn (string $modal) => route('admin.users.index', array_merge($listQuery, ["show_{$modal}" => $user->id]));
    $isSelf = $user->id === auth()->id();
    $isBanned = $user->status === \App\Models\User::STATUS_BANNED;
    $itemClass = 'flex w-full items-center gap-2.5 px-3.5 py-2.5 text-sm whitespace-nowrap';
@endphp

<div
    class="relative inline-flex"
    x-data="{
        open: false,
        menuTop: 0,
        menuLeft: 0,
        menuWidth: 260,
        placeMenu() {
            const trigger = this.$refs.trigger;
            if (! trigger) return;
            const rect = trigger.getBoundingClientRect();
            const width = this.menuWidth;
            const left = Math.max(8, Math.min(rect.right - width, window.innerWidth - width - 8));
            const top = rect.bottom + 6;
            const estimatedHeight = 420;
            const maxTop = window.innerHeight - estimatedHeight - 8;
            this.menuTop = Math.max(8, Math.min(top, maxTop));
            this.menuLeft = left;
        },
        toggle() {
            this.open = ! this.open;
            if (this.open) {
                this.$nextTick(() => this.placeMenu());
            }
        },
        close() {
            this.open = false;
        }
    }"
    @keydown.escape.window="close()"
    @admin-sidebar-close.window="close()"
    @click.outside="close()"
>
    <button
        type="button"
        x-ref="trigger"
        @click.stop="toggle()"
        class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
        aria-label="{{ __('admin.users.actions.menu') }}"
        aria-haspopup="menu"
        :aria-expanded="open"
    >
        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/></svg>
    </button>

    <div
        x-ref="panel"
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed z-[90] max-h-[min(70vh,28rem)] w-max min-w-[15rem] max-w-[calc(100vw-1rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white py-1 shadow-xl"
        :style="`top:${menuTop}px;left:${menuLeft}px;width:${menuWidth}px`"
        role="menu"
        @click.stop
    >
        <a href="{{ $modalUrl('info') }}" class="{{ $itemClass }} text-slate-700 hover:bg-slate-50" role="menuitem">
            <svg class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <span class="truncate">{{ __('admin.users.actions.view_info') }}</span>
        </a>
        <a href="{{ route('admin.users.edit', $user) }}" class="{{ $itemClass }} text-slate-700 hover:bg-slate-50" role="menuitem">
            <svg class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span class="truncate">{{ __('admin.actions.edit') }}</span>
        </a>
        <a href="{{ $modalUrl('balance') }}" class="{{ $itemClass }} text-slate-700 hover:bg-slate-50" role="menuitem">
            <svg class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <span class="truncate">{{ __('admin.users.actions.update_balance') }}</span>
        </a>
        <a href="{{ $modalUrl('deposit') }}" class="{{ $itemClass }} text-slate-700 hover:bg-slate-50" role="menuitem">
            <svg class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span class="truncate">{{ __('admin.users.actions.deposit') }}</span>
        </a>
        <a href="{{ $modalUrl('password') }}" class="{{ $itemClass }} text-slate-700 hover:bg-slate-50" role="menuitem">
            <svg class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            <span class="truncate">{{ __('admin.users.actions.change_password') }}</span>
        </a>
        <a href="{{ $modalUrl('payment_password') }}" class="{{ $itemClass }} text-slate-700 hover:bg-slate-50" role="menuitem">
            <svg class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span class="truncate">{{ __('admin.users.actions.change_payment_password') }}</span>
        </a>
        @if ($user->isShop())
            <a href="{{ $modalUrl('distributions') }}" class="{{ $itemClass }} text-slate-700 hover:bg-slate-50" role="menuitem">
                <svg class="size-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <span class="truncate">{{ __('admin.users.distribute_products') }}</span>
            </a>
        @endif

        @unless ($isSelf)
            <form method="POST" action="{{ route('admin.users.toggle-lock', $user) }}" onsubmit="return confirm(@js($isBanned ? __('admin.users.actions.confirm_unlock_account') : __('admin.users.actions.confirm_lock_account')))">
                @csrf
                <button type="submit" class="{{ $itemClass }} text-amber-600 hover:bg-amber-50" role="menuitem">
                    <svg class="size-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                    <span class="truncate">{{ $isBanned ? __('admin.users.actions.unlock_account') : __('admin.users.actions.lock_account') }}</span>
                </button>
            </form>
        @endunless

        <form method="POST" action="{{ route('admin.users.toggle-distribution-lock', $user) }}" onsubmit="return confirm(@js($user->distribution_locked ? __('admin.users.actions.confirm_unlock_distribution') : __('admin.users.actions.confirm_lock_distribution')))">
            @csrf
            <button type="submit" class="{{ $itemClass }} text-amber-600 hover:bg-amber-50" role="menuitem">
                <svg class="size-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>
                <span class="truncate">{{ $user->distribution_locked ? __('admin.users.actions.unlock_distribution') : __('admin.users.actions.lock_distribution') }}</span>
            </button>
        </form>

        @unless ($isSelf)
            <div class="my-1 border-t border-slate-100"></div>
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm(@js(__('admin.users.actions.confirm_delete')))">
                @csrf @method('DELETE')
                <button type="submit" class="{{ $itemClass }} text-red-600 hover:bg-red-50" role="menuitem">
                    <svg class="size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span class="truncate">{{ __('admin.actions.delete') }}</span>
                </button>
            </form>
        @endunless
    </div>
</div>
