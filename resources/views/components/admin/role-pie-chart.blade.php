@props(['data'])

@php
    use App\Support\Admin\DashboardRoleChart;

    $segments = DashboardRoleChart::segments($data);
@endphp

@if ($segments->isEmpty())
    <div class="flex h-64 items-center justify-center text-sm text-gray-400">{{ __('admin.dashboard.no_chart_data') }}</div>
@else
    <div class="flex min-h-64 flex-col items-center justify-center gap-5 py-2">
        <svg viewBox="0 0 120 120" class="h-48 w-48 shrink-0" aria-hidden="true">
            @foreach ($segments as $segment)
                <path d="{{ $segment['path'] }}" fill="{{ $segment['color'] }}">
                    <title>{{ $segment['label'] }}: {{ $segment['count'] }}</title>
                </path>
            @endforeach
        </svg>
        <div class="flex flex-wrap items-center justify-center gap-x-5 gap-y-2">
            @foreach ($segments as $segment)
                <div class="flex items-center gap-2 text-sm">
                    <span class="size-3 rounded-sm" style="background: {{ $segment['color'] }}"></span>
                    <span class="text-gray-700">{{ $segment['label'] }}</span>
                    <span class="text-gray-400">({{ $segment['count'] }})</span>
                </div>
            @endforeach
        </div>
    </div>
@endif
