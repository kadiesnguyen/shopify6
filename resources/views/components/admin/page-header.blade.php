@props(['title', 'actionUrl' => null, 'actionLabel' => null])

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h1 class="text-xl font-semibold text-slate-900">{{ $title }}</h1>
    @if (isset($actions))
        <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
    @elseif ($actionUrl)
        <a href="{{ $actionUrl }}" class="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white hover:bg-brand-dark">
            {{ $actionLabel ?? __('admin.actions.add') }}
        </a>
    @endif
</div>
