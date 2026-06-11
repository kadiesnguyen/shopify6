@props(['user'])

@if ($user->shopApplications->contains('status', 'pending'))
    <a
        href="{{ route('admin.shop-applications.index', ['status' => 'pending', 'q' => $user->email ?: $user->phone]) }}"
        class="mt-1 inline-flex w-fit items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 transition hover:bg-amber-100"
        title="{{ __('admin.users.shop_application_pending') }}"
    >
        <svg class="size-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ __('admin.users.shop_application_pending') }}
    </a>
@endif
